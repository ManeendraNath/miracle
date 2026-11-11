<?php

declare(strict_types=1);

namespace common\services\payment;

use common\models\Invoice;
use common\models\Transaction;
use common\services\admin\AdminAlertService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\PaymentConfirmationService;
use common\services\billing\PaymentProcessingService;
use common\services\notification\NotificationService;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\StreamTransport;
use yii\httpclient\Transport;

/**
 * Razorpay Standard Checkout: order creation, signature checks and capture.
 *
 * Keys from .env through params['razorpay']: RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET, RAZORPAY_WEBHOOK_SECRET.
 * Customers see it when Site Settings > Payments switches it on for clients in India and/or abroad.
 *
 * A payment is captured from two independent signals, whichever arrives first:
 * the browser callback (checkout signature) and the payment.captured webhook.
 * capture() is idempotent, so the second one is a no-op.
 */
final class RazorpayService
{
    private const API_URL = 'https://api.razorpay.com/v1/';

    /** Marks an unpaid order replaced after a coupon changed the invoice total (InvoiceCouponService). */
    public const SUPERSEDED_NOTE = 'SUPERSEDED: invoice total changed by a coupon before this order was paid.';
    /** Closed by staff (TransactionController::actionCloseOnline) after the gateway showed no payment. */
    public const CLOSED_NOTE = 'CLOSED: marked as not paid by staff after checking the payment gateway.';
    /** Closed by staff without the check: Razorpay answered that it does not know the order (e.g. made with other keys). */
    public const CLOSED_UNCHECKED_NOTE = 'CLOSED: marked as not paid by staff; Razorpay did not know this order, so it was not checked.';
    /** Failed online orders that are still recorded if money arrives after all (a checkout left open, a late webhook). */
    public const RECORDABLE_FAILED_NOTES = [self::SUPERSEDED_NOTE, self::CLOSED_NOTE, self::CLOSED_UNCHECKED_NOTE];

    /** Set by recoverPaidOrders(). */
    public bool $paidButNotRecorded = false;

    private array $_config;

    /** @param Transport|null $transport tests pass a MockTransport; null = real HTTP */
    public function __construct(?array $config = null, private ?Transport $_transport = null)
    {
        $this->_config = $config ?? (Yii::$app->params['razorpay'] ?? []);
    }

    public function isEnabled(): bool
    {
        return ($this->_config['keyId'] ?? '') !== ''
            && ($this->_config['keySecret'] ?? '') !== '';
    }

    /**
     * Switched on in Site Settings for the client's location ($abroad: billing country not India). Customers never
     * get a test-mode checkout on live: a test card would mark the invoice paid with no money received.
     */
    public function acceptsCustomerPayments(bool $abroad): bool
    {
        return (bool) \common\components\CustomYii::getSetting($abroad ? 'razorpay_abroad' : 'razorpay_india', 0)
            && $this->isEnabled() && !(YII_ENV_PROD && $this->isTestMode());
    }

    public function getKeyId(): string
    {
        return (string) ($this->_config['keyId'] ?? '');
    }

    /**
     * Returns a Pending Razorpay transaction with an order for the invoice's
     * outstanding amount, reusing an earlier unpaid order for the same amount.
     */
    public function createOrder(Invoice $invoice, float $amount): Transaction
    {
        if (!$this->isEnabled()) {
            throw new \DomainException('Online payment is not available.');
        }
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \DomainException('This invoice has no outstanding balance.');
        }

        $existing = Transaction::find()
            ->where([
                'invoice_id' => $invoice->id,
                'gateway_id' => Transaction::GATEWAY_RAZORPAY,
                'status' => Transaction::STATUS_PENDING,
            ])
            ->andWhere(['not', ['gateway_order_id' => null]])
            // Only recent orders: an older one may have been paid in a checkout long forgotten.
            ->andWhere(['>=', 'created_at', time() - 86400])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($existing !== null && $this->toMinor((float) $existing->amount) === $this->toMinor($amount)) {
            return $existing;
        }

        $orderId = $this->requestOrder($this->toMinor($amount), (string) $invoice->invoice_number, ['invoice_number' => (string) $invoice->invoice_number]);

        $now = time();
        $transaction = new Transaction();
        $transaction->invoice_id = (int) $invoice->id;
        $transaction->gateway_id = Transaction::GATEWAY_RAZORPAY;
        $transaction->gateway_order_id = $orderId;
        $transaction->amount = $amount;
        $transaction->status = Transaction::STATUS_PENDING;
        $transaction->created_at = $now;
        $transaction->updated_at = $now;
        if (!$transaction->save()) {
            throw new \RuntimeException('Payment could not be recorded: ' . json_encode($transaction->errors));
        }

        return $transaction;
    }

    /**
     * Locks the invoice's unpaid online orders (Razorpay, PayPal). Call BEFORE locking the invoice row: capture() locks
     * the order first and the invoice second, so the same order avoids deadlocks.
     */
    public static function lockUnpaidOrders(int $invoiceId): void
    {
        Yii::$app->db->createCommand(
            'SELECT id FROM {{%transaction}} WHERE invoice_id = :invoice AND gateway_id IN (' . implode(',', Transaction::ONLINE_GATEWAYS) . ') AND status = :status AND gateway_payment_id IS NULL FOR UPDATE',
            [':invoice' => $invoiceId, ':status' => Transaction::STATUS_PENDING]
        )->queryColumn();
    }

    /**
     * Retires the invoice's unpaid orders after its total changed: the next "Pay online" creates one for
     * the new total, and capture() still records a superseded order paid from a checkout left open.
     */
    public static function supersedeUnpaidOrders(int $invoiceId): void
    {
        Transaction::updateAll(
            ['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => self::SUPERSEDED_NOTE, 'updated_at' => time()],
            self::unpaidOrderCondition($invoiceId)
        );
    }

    /** Every other row: proofs, manual payments, paid orders (NULL-safe, unlike ['not', unpaidOrderCondition()]). */
    public static function notUnpaidOrderCondition(): array
    {
        return ['or', ['gateway_id' => null], ['not in', 'gateway_id', Transaction::ONLINE_GATEWAYS], ['not', ['gateway_payment_id' => null]]];
    }

    /** Online (Razorpay, PayPal) orders created for the invoice but not paid yet. */
    public static function unpaidOrderCondition(int $invoiceId): array
    {
        return [
            'invoice_id' => $invoiceId,
            'gateway_id' => Transaction::ONLINE_GATEWAYS,
            'status' => Transaction::STATUS_PENDING,
            'gateway_payment_id' => null,
        ];
    }

    /** Test keys (rzp_test_…): payments are simulated and no money moves. */
    public function isTestMode(): bool
    {
        return str_starts_with($this->getKeyId(), 'rzp_test_');
    }

    /**
     * A bare Razorpay order for the admin "Razorpay Test" page: nothing is stored, no invoice is
     * involved (its webhook is ignored as "not our order"). Refused with live keys.
     */
    public function createTestOrder(int $amountMinor): string
    {
        if (!$this->isEnabled() || !$this->isTestMode()) {
            throw new \DomainException('Test payments need Razorpay test keys (rzp_test_…) in .env.');
        }
        if ($amountMinor < 100) {
            throw new \DomainException('The minimum amount is ₹1.');
        }

        return $this->requestOrder($amountMinor, 'gateway-test-' . time(), ['purpose' => 'admin gateway test']);
    }

    /** The payment as Razorpay reports it (status, method, amount...). */
    public function fetchPayment(string $paymentId): array
    {
        try {
            $response = $this->api()->get('payments/' . rawurlencode($paymentId))->send();
        } catch (\yii\httpclient\Exception $e) {
            throw new \RuntimeException('Razorpay could not be reached: ' . $e->getMessage(), 0, $e);
        }
        if (!$response->isOk || !is_array($response->data)) {
            throw new \RuntimeException('Razorpay did not return payment ' . $paymentId . ' (HTTP ' . $response->statusCode . ').');
        }

        return $response->data;
    }

    /**
     * Refunds $amountMinor of a captured payment through Razorpay and returns the refund id. Returns null
     * when Razorpay already shows exactly that amount refunded beyond what the app has recorded
     * ($recordedRefundMinor), e.g. a refund made from the dashboard: then only the record is missing.
     */
    public function refundPayment(string $paymentId, int $amountMinor, int $recordedRefundMinor = 0): ?string
    {
        try {
            $payment = $this->fetchPayment($paymentId);
        } catch (\RuntimeException $e) {
            throw new \DomainException('Could not check the payment with Razorpay, nothing was refunded: ' . $e->getMessage(), 0, $e);
        }
        $unrecorded = (int) ($payment['amount_refunded'] ?? 0) - $recordedRefundMinor;
        if ($unrecorded === $amountMinor) {
            return null;
        }
        if ($unrecorded < 0) {
            throw new \DomainException('Razorpay shows less refunded on this payment than recorded here (a refund may have failed). Check the refund in the Razorpay dashboard before refunding more.');
        }
        if ($unrecorded !== 0) {
            $shown = number_format(max($unrecorded, 0) / 100, 2);
            throw new \DomainException("Razorpay shows ₹{$shown} refunded on this payment that is not recorded here. Enter ₹{$shown} as the refund amount to record it first.");
        }
        if (!in_array($payment['status'] ?? null, ['captured', 'refunded'], true)) {
            throw new \DomainException('Razorpay shows this payment as "' . ($payment['status'] ?? 'unknown') . '", so it cannot be refunded.');
        }

        $unknown = 'No clear answer from Razorpay, so the refund may have been sent. Check this payment in the Razorpay dashboard before trying again.';
        try {
            $response = $this->api()
                // Same key for a retry of this refund, new key once it is recorded: a timed-out request that did get through isn't sent twice.
                ->post('payments/' . rawurlencode($paymentId) . '/refund', ['amount' => $amountMinor, 'speed' => 'normal'], ['X-Refund-Idempotency' => "refund-{$paymentId}-{$recordedRefundMinor}-{$amountMinor}"])
                ->setFormat(Client::FORMAT_JSON)
                ->send();
        } catch (\yii\httpclient\Exception $e) {
            Yii::error(['message' => 'Razorpay refund request failed.', 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            throw new \DomainException($unknown, 0, $e);
        }
        if ((int) $response->statusCode === 409) {
            throw new \DomainException('Razorpay is still working on this refund. Wait a few minutes, then check this payment in the Razorpay dashboard before trying again.');
        }
        if ((int) $response->statusCode >= 500) {
            Yii::error(['message' => 'Razorpay refund answered with a server error.', 'paymentId' => $paymentId, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \DomainException($unknown);
        }
        $refundId = $response->isOk ? ($response->data['id'] ?? null) : null;
        if (!is_string($refundId) || $refundId === '') {
            Yii::error(['message' => 'Razorpay refund failed.', 'paymentId' => $paymentId, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \DomainException('Razorpay refused the refund: ' . ($response->data['error']['description'] ?? 'HTTP ' . $response->statusCode) . '.');
        }

        return $refundId;
    }

    /** Whether Razorpay already shows exactly this amount refunded beyond the record: refunding it only writes the record. */
    public function isAlreadyRefunded(string $paymentId, int $amountMinor, int $recordedRefundMinor): bool
    {
        try {
            return (int) ($this->fetchPayment($paymentId)['amount_refunded'] ?? 0) - $recordedRefundMinor === $amountMinor;
        } catch (\RuntimeException $e) {
            throw new \DomainException('Could not check the payment with Razorpay: ' . $e->getMessage(), 0, $e);
        }
    }

    /** Refuses when Razorpay's refunded total differs from the one recorded here, e.g. after a dashboard refund. */
    public function assertRefundsRecorded(Transaction $transaction): void
    {
        try {
            $refunded = (int) ($this->fetchPayment((string) $transaction->gateway_payment_id)['amount_refunded'] ?? 0);
        } catch (\RuntimeException $e) {
            throw new \DomainException('Could not check the payment with Razorpay: ' . $e->getMessage(), 0, $e);
        }
        if ($refunded !== $this->toMinor((float) $transaction->refunded_amount)) {
            throw new \DomainException('Razorpay shows ₹' . number_format($refunded / 100, 2) . ' refunded on this payment but ₹' . number_format((float) $transaction->refunded_amount, 2) . ' is recorded here. Record it with Refund first.');
        }
    }

    /**
     * Cron safety net for payments whose browser callback and webhook were both lost:
     * asks Razorpay about orders still pending after $minAge seconds (up to $maxAge old) and records
     * any captured payment, then fills in Razorpay's fee where it is still missing, and alerts staff
     * about refunds made in the Razorpay dashboard, which nothing else reports.
     *
     * ponytail: one API call per unpaid order of the last 30 days each run, newest first and stopped after 10 minutes;
     * expire old orders here if abandoned checkouts ever outgrow that.
     *
     * @return array{captured: int, fees: int, unrecordedRefunds: int, errors: int}
     */
    public function syncPending(int $minAge = 1800, int $maxAge = 30 * 86400): array
    {
        $result = ['captured' => 0, 'fees' => 0, 'unrecordedRefunds' => 0, 'errors' => 0];
        $now = time();
        $orders = Transaction::find()
            ->where(['gateway_id' => Transaction::GATEWAY_RAZORPAY])
            ->andWhere(['not', ['gateway_order_id' => null]])
            ->andWhere(['or', ['status' => Transaction::STATUS_PENDING], ['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => self::RECORDABLE_FAILED_NOTES]])
            ->andWhere(['between', 'created_at', $now - $maxAge, $now - $minAge])
            ->orderBy(['id' => SORT_DESC])
            ->all();
        foreach ($orders as $order) {
            if (time() - $now > 600) {
                $result['errors']++;
                Yii::warning('Razorpay order check stopped early.', __METHOD__);
                break;
            }
            $payment = null;
            try {
                $payment = $this->capturedPayment((string) $order->gateway_order_id);
                if ($payment !== null) {
                    $this->recordFound((string) $order->gateway_order_id, $payment);
                    $result['captured']++;
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                Yii::error(['message' => 'Razorpay sync failed for an order.', 'orderId' => $order->gateway_order_id, 'error' => $e->getMessage()], __METHOD__);
                $this->alertIfRefused($e, (string) $order->gateway_order_id, $payment);
            }
        }

        // Payments recorded from the browser callback have no fee until the webhook or this sync adds it.
        $unpriced = Transaction::find()
            ->where(['gateway_id' => Transaction::GATEWAY_RAZORPAY, 'status' => [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED]])
            ->andWhere(['not', ['gateway_payment_id' => null]])
            ->andWhere(['or', ['gateway_fee' => null], ['gateway_fee' => 0]])
            ->andWhere(['>=', 'paid_at', $now - 30 * 86400])
            ->orderBy(['id' => SORT_DESC])
            ->limit(50)
            ->all();
        foreach ($unpriced as $payment) {
            try {
                $fee = $this->fetchPayment((string) $payment->gateway_payment_id)['fee'] ?? null;
                if (is_int($fee) && $fee > 0 && $this->saveFee($payment, $fee)) {
                    $result['fees']++;
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                Yii::error(['message' => 'Razorpay fee lookup failed.', 'paymentId' => $payment->gateway_payment_id, 'error' => $e->getMessage()], __METHOD__);
            }
        }

        // Fully refunded rows too: Razorpay showing less than recorded means a refund failed or was reversed.
        // ponytail: newest 200 payments of the last 180 days (Razorpay's refund window), one API call each every run; subscribe to the refund webhooks if volume outgrows this.
        $failures = 0;
        $paid = Transaction::find()
            ->where(['gateway_id' => Transaction::GATEWAY_RAZORPAY, 'status' => [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED]])
            ->andWhere(['not', ['gateway_payment_id' => null]])
            ->andWhere(['>=', 'paid_at', $now - 180 * 86400])
            ->orderBy(['id' => SORT_DESC])
            ->limit(200)
            ->all();
        foreach ($paid as $payment) {
            // A slow or unreachable Razorpay must not hold the run for hours (20 s per call); the next run checks again.
            if ($failures >= 5 || time() - $now > 1200) {
                $result['errors']++;
                Yii::warning('Razorpay refund check stopped early.', __METHOD__);
                break;
            }
            try {
                $refunded = $this->refundedMinor($payment);
                if ($refunded !== $this->toMinor((float) $payment->refunded_amount)) {
                    // A staff refund may be half way: Razorpay already shows it, its row isn't saved yet. Wait for that row, then look again.
                    $refunded = Yii::$app->db->transaction(function () use ($payment): int {
                        Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $payment->id)->queryScalar();
                        $payment->refresh();

                        return $this->refundedMinor($payment);
                    });
                }
                if ($refunded !== $this->toMinor((float) $payment->refunded_amount)) {
                    $result['unrecordedRefunds']++;
                    AdminAlertService::refundNotRecorded((string) $payment->gateway_payment_id, $refunded / 100, (float) $payment->refunded_amount, (int) $payment->id);
                }
                $failures = 0;
            } catch (\Throwable $e) {
                $failures++;
                $result['errors']++;
                Yii::error(['message' => 'Razorpay refund check failed.', 'paymentId' => $payment->gateway_payment_id, 'error' => $e->getMessage()], __METHOD__);
            }
        }

        return $result;
    }

    /**
     * Before starting a new payment: was an earlier order of this invoice paid without us hearing?
     * Razorpay not answering stops the checks and doesn't stop the customer; Razorpay saying "paid" when we can't
     * record it sets $paidButNotRecorded, and then they must not be offered another checkout.
     *
     * @return int payments recorded
     */
    public function recoverPaidOrders(int $invoiceId): int
    {
        $orders = Transaction::find()
            ->where(['invoice_id' => $invoiceId, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_payment_id' => null])
            ->andWhere(['not', ['gateway_order_id' => null]])
            ->andWhere(['or', ['status' => Transaction::STATUS_PENDING], ['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => self::RECORDABLE_FAILED_NOTES]])
            ->orderBy(['id' => SORT_DESC])
            ->limit(5)
            ->all();
        $recorded = 0;
        foreach ($orders as $order) {
            $orderId = (string) $order->gateway_order_id;
            try {
                $payment = $this->capturedPayment($orderId);
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Could not check an earlier Razorpay order.', 'orderId' => $orderId, 'error' => $e->getMessage()], __METHOD__);
                break;
            }
            if ($payment === null) {
                continue;
            }
            try {
                $this->recordFound($orderId, $payment);
                $recorded++;
            } catch (\Throwable $e) {
                $this->paidButNotRecorded = true;
                Yii::error(['message' => 'An earlier Razorpay payment could not be recorded.', 'orderId' => $orderId, 'error' => $e->getMessage()], __METHOD__);
                $this->alertIfRefused($e, $orderId, $payment);
            }
        }

        return $recorded;
    }

    /**
     * Staff "Check with Razorpay" on one order: records it if Razorpay has a captured payment for it.
     * True when one was recorded; throws when Razorpay can't be asked or refuses the record.
     */
    public function checkOrder(Transaction $order): bool
    {
        $payment = $this->capturedPayment((string) $order->gateway_order_id);
        if ($payment === null) {
            return false;
        }
        $this->recordFound((string) $order->gateway_order_id, $payment);

        return true;
    }

    /** The order's captured payment as Razorpay reports it, or null. */
    private function capturedPayment(string $orderId): ?array
    {
        $response = $this->api()->get('orders/' . rawurlencode($orderId) . '/payments')->send();
        if (!$response->isOk) {
            throw new \RuntimeException('HTTP ' . $response->statusCode);
        }
        foreach ($response->data['items'] ?? [] as $payment) {
            if (($payment['status'] ?? null) === 'captured') {
                return $payment;
            }
        }

        return null;
    }

    /** Found long after the fact: dated when the customer paid, so it lands in the right month. */
    private function recordFound(string $orderId, array $payment): void
    {
        $this->capture($orderId, (string) $payment['id'], (int) $payment['amount'], isset($payment['fee']) ? (int) $payment['fee'] : null, isset($payment['created_at']) ? (int) $payment['created_at'] : null);
    }

    /** A refusal (amount mismatch, order closed) won't change on its own, so staff need the reference. A lock timeout will: it retries. */
    private function alertIfRefused(\Throwable $e, string $orderId, ?array $payment): void
    {
        if ($e instanceof \DomainException && $payment !== null) {
            AdminAlertService::paymentNotRecorded($orderId, (string) ($payment['id'] ?? ''), (int) ($payment['amount'] ?? 0) / 100, $e->getMessage());
        }
    }

    private function refundedMinor(Transaction $payment): int
    {
        return (int) ($this->fetchPayment((string) $payment->gateway_payment_id)['amount_refunded'] ?? 0);
    }

    /** Razorpay's fee for the payment, GST included (what Razorpay keeps from the settlement). */
    private function saveFee(Transaction $transaction, int $feeMinor): bool
    {
        $transaction->gateway_fee = $feeMinor / 100;
        $transaction->updated_at = time();

        return $transaction->save(false, ['gateway_fee', 'updated_at']);
    }

    /** Creates an order and returns its id. */
    private function requestOrder(int $amountMinor, string $receipt, array $notes): string
    {
        $response = $this->api()
            // payment_capture: capture on payment even if the account is set to manual capture; the app treats
            // a verified checkout as paid.
            ->post('orders', ['amount' => $amountMinor, 'currency' => 'INR', 'receipt' => substr($receipt, 0, 40), 'notes' => $notes, 'payment_capture' => 1])
            ->setFormat(Client::FORMAT_JSON)
            ->send();

        $orderId = $response->isOk ? ($response->data['id'] ?? null) : null;
        if (!is_string($orderId) || $orderId === '') {
            Yii::error(['message' => 'Razorpay order creation failed.', 'receipt' => $receipt, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \RuntimeException('Could not start online payment. Please try again.');
        }

        return $orderId;
    }

    private function api(): Client
    {
        return new Client([
            'baseUrl' => self::API_URL,
            'transport' => $this->_transport ?? StreamTransport::class,
            'requestConfig' => [
                'headers' => ['Authorization' => 'Basic ' . base64_encode($this->_config['keyId'] . ':' . $this->_config['keySecret'])],
                // StreamTransport doesn't verify certificates unless told to.
                'options' => ['timeout' => 20, 'sslVerifyPeer' => true],
            ],
        ]);
    }

    /** Signature returned to the browser by Checkout: HMAC(order_id|payment_id, key_secret). */
    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $secret = (string) ($this->_config['keySecret'] ?? '');
        // An empty secret would make every HMAC forgeable: fail closed.
        if ($secret === '') {
            return false;
        }
        return $signature !== '' && hash_equals(hash_hmac('sha256', $orderId . '|' . $paymentId, $secret), $signature);
    }

    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        $secret = (string) ($this->_config['webhookSecret'] ?? '');
        if ($secret === '') {
            throw new \RuntimeException('Razorpay webhook secret is not configured.');
        }
        return $signature !== '' && hash_equals(hash_hmac('sha256', $body, $secret), $signature);
    }

    /**
     * Marks the order's transaction Captured and settles the invoice. Idempotent.
     *
     * @param int|null $amountMinor amount reported by Razorpay in paise; null when
     *                              the caller only has a verified checkout signature
     *                              (the order itself fixes the amount)
     * @param int|null $feeMinor    Razorpay's fee incl. GST in paise, when known (webhook, sync)
     * @param int|null $paidAt      when Razorpay says it was paid, when known; default now
     * @throws \DomainException when the payment does not match a pending order
     */
    public function capture(string $orderId, string $paymentId, ?int $amountMinor = null, ?int $feeMinor = null, ?int $paidAt = null): Transaction
    {
        $db = Yii::$app->db;
        $dbTransaction = $db->beginTransaction();
        try {
            $id = $db->createCommand('SELECT id FROM {{%transaction}} WHERE gateway_order_id = :orderId FOR UPDATE')
                ->bindValue(':orderId', $orderId)
                ->queryScalar();
            $transaction = $id !== false ? Transaction::findOne((int) $id) : null;
            if ($transaction === null) {
                throw new \DomainException('Payment transaction does not exist.');
            }
            // Refunded: recorded earlier and since returned, e.g. a webhook retried after a full refund.
            if (in_array($transaction->status, [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED], true)) {
                if ($transaction->gateway_payment_id !== $paymentId) {
                    throw new \DomainException('Captured payment reference does not match.');
                }
                // The webhook usually arrives after the browser callback and is the first to know the fee.
                if ($feeMinor !== null && (float) $transaction->gateway_fee <= 0) {
                    $this->saveFee($transaction, $feeMinor);
                }
                $dbTransaction->commit();
                // A replay is the only retry there is when the first confirmation email failed.
                if ($transaction->status === Transaction::STATUS_CAPTURED && !$transaction->isUnapplied() && empty($transaction->confirmation_emailed_at)) {
                    $this->sendConfirmation($transaction);
                }
                return $transaction;
            }
            // A superseded order can still be paid from a checkout that was already open.
            // The customer was charged, so record it; the invoice is reconciled against its new total.
            // So can an order staff closed as not paid: the money arrived after all.
            $superseded = $transaction->status === Transaction::STATUS_FAILED
                && $transaction->user_payment_notes === self::SUPERSEDED_NOTE;
            $recordable = $transaction->status === Transaction::STATUS_FAILED
                && in_array($transaction->user_payment_notes, self::RECORDABLE_FAILED_NOTES, true);
            if ($transaction->status !== Transaction::STATUS_PENDING && !$recordable) {
                throw new \DomainException('Payment transaction is not pending.');
            }
            if ($amountMinor !== null && $this->toMinor((float) $transaction->amount) !== $amountMinor) {
                throw new \DomainException('Payment amount does not match.');
            }

            $now = time();
            $transaction->gateway_payment_id = $paymentId;
            $transaction->status = Transaction::STATUS_CAPTURED;
            $transaction->paid_at = $paidAt !== null && $paidAt > 0 && $paidAt <= $now ? $paidAt : $now;
            $transaction->updated_at = $now;
            if ($feeMinor !== null) {
                $transaction->gateway_fee = $feeMinor / 100;
            }

            // The customer has been charged, so the capture is always recorded. If the invoice
            // can no longer take payments (cancelled, carried forward, or already paid some other
            // way, e.g. an approved UPI proof) it is left unapplied for an admin to refund.
            // Locked (transaction row first, then invoice, as approve does) so a concurrent
            // carry-forward or cancel can't change the status between this check and settling.
            $db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $transaction->invoice_id)->queryScalar();
            $invoice = Invoice::findOne((int) $transaction->invoice_id);
            $unapplied = $invoice !== null && in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_CARRIED_FORWARD, Invoice::STATUS_PAID, Invoice::STATUS_REFUNDED, Invoice::STATUS_DRAFT], true);
            if ($unapplied) {
                $transaction->is_unapplied = 1;
                $transaction->user_payment_notes = trim($transaction->user_payment_notes . "\n" . Transaction::UNAPPLIED_NOTE_PREFIX . ' invoice was ' . $invoice->statusLabel() . ' when this was recorded.');
            }
            if (!$transaction->save()) {
                throw new \RuntimeException('Captured payment could not be saved.');
            }
            if (!$unapplied) {
                (new PaymentProcessingService())->transactionChanged($transaction, false);
            }
            $dbTransaction->commit();
            // Committed: a failed alert must not look like a failed payment to the caller.
            try {
                if (!$unapplied && $invoice !== null && $invoice->refresh()) {
                    // E.g. an older full-amount order paid after a part payment by cheque.
                    $excess = (new InvoicePaymentService())->getCapturedAmount((int) $invoice->id) - (float) $invoice->total_payable;
                    if ($this->toMinor($excess) > 0) {
                        AdminAlertService::paymentOverpaid($invoice->invoice_number, (float) $transaction->amount, round($excess, 2), $paymentId);
                    } elseif ($superseded && $this->toMinor((float) $invoice->total_payable) !== $this->toMinor((float) $transaction->amount)) {
                        AdminAlertService::supersededOrderPaid($invoice->invoice_number, (float) $transaction->amount, (float) $invoice->total_payable, $paymentId);
                    }
                }
                if ($unapplied) {
                    Yii::warning(['message' => 'Razorpay payment captured on a non-payable invoice.', 'transactionId' => (int) $transaction->id, 'invoice' => $invoice->reference(), 'status' => $invoice->status], __METHOD__);
                    AdminAlertService::paymentUnapplied($invoice->reference(), (string) $invoice->status, (float) $transaction->amount, $paymentId);
                    (new NotificationService())->create((int) $invoice->user_id, 'payment', 'Payment Received: Not Yet Applied', 'We received your payment of ₹' . number_format((float) $transaction->amount, 2) . ' for ' . $invoice->customerReference() . ', but that invoice was already settled or closed. We will refund it or apply it to another invoice.', '/payment/view?id=' . (int) $transaction->id);
                    (new PaymentConfirmationService())->sendHeld($invoice, (float) $transaction->amount);
                }
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Payment was captured but an alert failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
            }
            if ($unapplied) {
                return $transaction;
            }
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            throw $e;
        }

        // Notification first: a mail failure must not cost the customer both.
        try {
            (new NotificationService())->create((int) $invoice->user_id, 'payment', 'Payment Confirmed', 'Your payment for invoice ' . $invoice->invoice_number . ' has been confirmed.', '/invoice/view?number=' . rawurlencode((string) $invoice->invoice_number));
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment confirmed notification failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
        }
        $this->sendConfirmation($transaction);

        return $transaction;
    }

    /** Under the row lock, so the callback and the webhook arriving together send one email: the second sees it was sent. */
    private function sendConfirmation(Transaction $transaction): void
    {
        try {
            Yii::$app->db->transaction(static function () use ($transaction): void {
                Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $transaction->id)->queryScalar();
                $transaction->refresh();
                (new PaymentConfirmationService())->send($transaction);
            });
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Razorpay payment was captured but confirmation email failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    private function toMinor(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
