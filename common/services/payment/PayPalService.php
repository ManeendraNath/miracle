<?php

declare(strict_types=1);

namespace common\services\payment;

use common\components\CustomYii;
use common\models\Invoice;
use common\models\Transaction;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\Response;
use yii\httpclient\StreamTransport;
use yii\httpclient\Transport;

/**
 * PayPal Checkout (Orders v2) for clients abroad. Invoices are in rupees; PayPal charges the US dollar amount
 * at Site Settings' rate (usd_inr_rate), and the payment is recorded as the rupee amount it settles.
 *
 * Keys from .env through params['paypal']: PAYPAL_MODE (live|sandbox), PAYPAL_CLIENT_ID, PAYPAL_SECRET.
 * Shown to clients abroad when Site Settings > Payments switches it on.
 *
 * The browser approves the order and the server captures it, so money only moves through our capture call.
 * If the browser is lost after approval, the next "Pay" on that invoice and the 30-minute sync capture or record it.
 */
final class PayPalService
{
    private const LIVE_URL = 'https://api-m.paypal.com/';
    private const SANDBOX_URL = 'https://api-m.sandbox.paypal.com/';

    /** Set by recoverPaidOrders(): PayPal shows a completed payment the app could not record. */
    public bool $paidButNotRecorded = false;

    private array $_config;
    private ?string $_token = null;

    /** @param Transport|null $transport tests pass a MockTransport; null = real HTTP */
    public function __construct(?array $config = null, private ?Transport $_transport = null)
    {
        $this->_config = $config ?? (Yii::$app->params['paypal'] ?? []);
    }

    public function isEnabled(): bool
    {
        return ($this->_config['clientId'] ?? '') !== ''
            && ($this->_config['secret'] ?? '') !== '';
    }

    public function isSandbox(): bool
    {
        return !empty($this->_config['sandbox']);
    }

    /** Switched on for clients abroad, never a sandbox checkout on live (no money would move), and a rate known. */
    public function acceptsCustomerPayments(): bool
    {
        return (bool) CustomYii::getSetting('paypal_abroad', 0) && $this->isEnabled() && !(YII_ENV_PROD && $this->isSandbox()) && self::usdRate() > 0;
    }

    public function getClientId(): string
    {
        return (string) ($this->_config['clientId'] ?? '');
    }

    /** Rupees per US dollar charged (ExchangeRateService); 0 when none. */
    public static function usdRate(): float
    {
        return (new ExchangeRateService())->usdRate();
    }

    /** The US dollar amount PayPal charges for $inr, as PayPal writes it ("41.18"). */
    public static function toUsd(float $inr, float $rate): string
    {
        return number_format(round($inr / $rate, 2), 2, '.', '');
    }

    /** A Pending PayPal transaction with a new order for $amount rupees, charged in USD. */
    public function createOrder(Invoice $invoice, float $amount): Transaction
    {
        $rate = self::usdRate();
        if (!$this->isEnabled() || $rate <= 0) {
            throw new \DomainException('PayPal is not available.');
        }
        $amount = round($amount, 2);
        $usd = self::toUsd($amount, $rate);
        if ((float) $usd < 1) {
            throw new \DomainException('The remaining balance is below the US$1 minimum for PayPal. Please contact support.');
        }
        $response = $this->api()->post('v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $invoice->invoice_number,
                'custom_id' => (string) $invoice->invoice_number,
                'description' => 'Invoice ' . $invoice->invoice_number,
                'amount' => ['currency_code' => 'USD', 'value' => $usd],
            ]],
            'application_context' => ['brand_name' => substr((string) Yii::$app->name, 0, 127), 'shipping_preference' => 'NO_SHIPPING'],
        ])->setFormat(Client::FORMAT_JSON)->send();
        $orderId = $response->isOk ? ($response->data['id'] ?? null) : null;
        if (!is_string($orderId) || $orderId === '') {
            Yii::error(['message' => 'PayPal order creation failed.', 'invoice' => $invoice->invoice_number, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \RuntimeException('Could not start PayPal payment. Please try again.');
        }

        $now = time();
        $transaction = new Transaction();
        $transaction->invoice_id = (int) $invoice->id;
        $transaction->gateway_id = Transaction::GATEWAY_PAYPAL;
        $transaction->gateway_order_id = $orderId;
        $transaction->amount = $amount;
        $transaction->raw_payload = json_encode(['currency' => 'USD', 'usd' => $usd, 'rate' => $rate], JSON_THROW_ON_ERROR);
        $transaction->status = Transaction::STATUS_PENDING;
        $transaction->created_at = $now;
        $transaction->updated_at = $now;
        if (!$transaction->save()) {
            throw new \RuntimeException('Payment could not be recorded: ' . json_encode($transaction->errors));
        }

        return $transaction;
    }

    /**
     * Captures an order the customer approved and records it. Money moves only here, so the order is checked first:
     * still unpaid, and the invoice still owes exactly its amount. Otherwise nothing is captured (PayPal lets the
     * approval lapse) and the row is Failed with the reason in user_payment_notes.
     *
     * @return Transaction Captured; Pending with a capture id while PayPal holds the payment; or Failed
     */
    public function captureOrder(string $orderId): Transaction
    {
        $refused = $this->refuseReason($orderId);
        if ($refused !== null) {
            return $refused;
        }
        // The request id makes a repeated capture (double click, retry) return the first result, never charge twice.
        $response = $this->api()->post('v2/checkout/orders/' . rawurlencode($orderId) . '/capture', null, ['PayPal-Request-Id' => 'capture-' . $orderId])
            ->setContent('{}')->addHeaders(['content-type' => 'application/json'])->send();
        $order = $response->isOk ? $response->data : null;
        if (!is_array($order) && str_contains((string) $response->content, 'ORDER_ALREADY_CAPTURED')) {
            $order = $this->fetchOrder($orderId);
        }
        if (!is_array($order)) {
            Yii::error(['message' => 'PayPal capture failed.', 'orderId' => $orderId, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \RuntimeException('PayPal could not complete the payment (HTTP ' . $response->statusCode . ').');
        }

        return $this->record($orderId, $order) ?? $this->transaction($orderId);
    }

    /**
     * Records what PayPal reports for a captured order: Captured (settles the invoice), Pending with the capture id
     * while PayPal holds the payment (the invoice shows it under review; no second payment), or Failed when declined.
     * Null when the order has not been captured at all.
     */
    public function record(string $orderId, array $order): ?Transaction
    {
        $transaction = $this->transaction($orderId);
        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (($order['status'] ?? '') !== 'COMPLETED' || !is_array($capture) || !is_string($capture['id'] ?? null)) {
            return null;
        }
        $status = (string) ($capture['status'] ?? '');
        if (in_array($status, ['DECLINED', 'FAILED'], true)) {
            if ($transaction->status === Transaction::STATUS_PENDING) {
                $transaction->updateAttributes(['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => 'PayPal declined the payment.', 'updated_at' => time()]);
            }
            return $transaction;
        }
        if ($status !== 'COMPLETED') {
            // Held by PayPal (e.g. review, eCheck): the capture id marks the row as a payment awaiting completion.
            if ($transaction->status === Transaction::STATUS_PENDING && (string) $transaction->gateway_payment_id === '') {
                $transaction->updateAttributes(['gateway_payment_id' => $capture['id'], 'updated_at' => time()]);
            }
            return $transaction;
        }
        $expected = json_decode((string) $transaction->raw_payload, true);
        $usd = is_array($expected) ? (string) ($expected['usd'] ?? '') : '';
        $rate = is_array($expected) ? (float) ($expected['rate'] ?? 0) : 0.0;
        if (($capture['amount']['currency_code'] ?? '') !== 'USD' || $usd === '' || self::toUsd((float) ($capture['amount']['value'] ?? 0), 1) !== $usd) {
            throw new \DomainException('Payment amount does not match: PayPal captured ' . ($capture['amount']['currency_code'] ?? '?') . ' ' . ($capture['amount']['value'] ?? '?') . ', expected USD ' . $usd . '.');
        }
        $feeUsd = $capture['seller_receivable_breakdown']['paypal_fee']['value'] ?? null;
        $feeMinor = is_numeric($feeUsd) && $rate > 0 ? (int) round((float) $feeUsd * $rate * 100) : null;
        $paidAt = isset($capture['create_time']) ? (strtotime((string) $capture['create_time']) ?: null) : null;

        // RazorpayService::capture() is the shared recorder: it settles the invoice from the order's transaction row,
        // whatever the gateway (locks, unapplied and superseded orders, confirmation email).
        return (new RazorpayService([]))->capture($orderId, $capture['id'], null, $feeMinor, $paidAt);
    }

    /**
     * Catches up with PayPal: captures an order the customer approved without the app hearing back ($recent only,
     * while the approval is current; captureOrder() checks it is still wanted) and records completed or held ones.
     */
    public function sync(Transaction $transaction, bool $recent): ?Transaction
    {
        $order = $this->fetchOrder((string) $transaction->gateway_order_id);
        if ($order === null) {
            return null;
        }
        if (($order['status'] ?? '') === 'APPROVED') {
            return $recent && $transaction->status === Transaction::STATUS_PENDING ? $this->captureOrder((string) $transaction->gateway_order_id) : null;
        }

        return ($order['status'] ?? '') === 'COMPLETED' ? $this->record((string) $transaction->gateway_order_id, $order) : null;
    }

    /** Before a new order: an earlier one may have been paid with the browser lost. Returns how many were recorded. */
    public function recoverPaidOrders(int $invoiceId): int
    {
        $recorded = 0;
        foreach ($this->unpaidOrders(['invoice_id' => $invoiceId], 86400) as $transaction) {
            try {
                $result = $this->sync($transaction, true);
                if ($result !== null && $result->status === Transaction::STATUS_CAPTURED) {
                    $recorded++;
                }
            } catch (\DomainException $e) {
                $this->paidButNotRecorded = true;
                $this->alertNotRecorded($transaction, $e->getMessage());
            } catch (\Throwable $e) {
                Yii::warning(['message' => 'PayPal order lookup failed.', 'orderId' => $transaction->gateway_order_id, 'error' => $e->getMessage()], __METHOD__);
            }
        }

        return $recorded;
    }

    /**
     * Cron (with the Razorpay sync, every 30 minutes): records orders paid in the last 3 days that the app missed,
     * and payments PayPal held until it completes or declines them (staff are alerted after 3 days).
     *
     * @return array{captured: int, errors: int}
     */
    public function syncPending(int $minAge = 600): array
    {
        $result = ['captured' => 0, 'errors' => 0];
        $held = Transaction::find()->where(['gateway_id' => Transaction::GATEWAY_PAYPAL, 'status' => Transaction::STATUS_PENDING])
            ->andWhere(['not', ['gateway_payment_id' => null]])->orderBy(['id' => SORT_ASC])->all();
        foreach ([...$this->unpaidOrders(['<=', 'created_at', time() - $minAge], 3 * 86400), ...$held] as $transaction) {
            try {
                $synced = $this->sync($transaction, (int) $transaction->created_at >= time() - 86400);
                if ($synced !== null && $synced->status === Transaction::STATUS_CAPTURED) {
                    $result['captured']++;
                } elseif ($synced !== null && $synced->status === Transaction::STATUS_PENDING && (string) $synced->gateway_payment_id !== '' && (int) $synced->created_at < time() - 3 * 86400) {
                    $this->alertNotRecorded($synced, 'PayPal has held this payment for over 3 days. Check it in PayPal.');
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                Yii::error(['message' => 'PayPal sync failed for an order.', 'orderId' => $transaction->gateway_order_id, 'error' => $e->getMessage()], __METHOD__);
                if ($e instanceof \DomainException) {
                    $this->alertNotRecorded($transaction, $e->getMessage());
                }
            }
        }

        return $result;
    }

    /** The row for $orderId, refusing (Failed, with the reason) a capture the invoice no longer wants; null = go ahead. */
    private function refuseReason(string $orderId): ?Transaction
    {
        $db = Yii::$app->db;
        $dbTransaction = $db->beginTransaction();
        try {
            // Order row first, then the invoice, as RazorpayService::capture() locks them.
            $id = $db->createCommand('SELECT id FROM {{%transaction}} WHERE gateway_order_id = :order AND gateway_id = :gateway FOR UPDATE', [':order' => $orderId, ':gateway' => Transaction::GATEWAY_PAYPAL])->queryScalar();
            $transaction = $id !== false ? Transaction::findOne((int) $id) : null;
            if ($transaction === null) {
                throw new \DomainException('Payment transaction does not exist.');
            }
            if ($transaction->status !== Transaction::STATUS_PENDING || (string) $transaction->gateway_payment_id !== '') {
                $dbTransaction->commit();
                // Already captured, held or refused: report that state, never capture again.
                return $transaction->status === Transaction::STATUS_PENDING && (string) $transaction->gateway_payment_id === '' ? null : $transaction;
            }
            $db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE', [':id' => (int) $transaction->invoice_id])->queryScalar();
            $invoice = Invoice::findOne((int) $transaction->invoice_id);
            $payments = new \common\services\billing\InvoicePaymentService();
            $reason = match (true) {
                $invoice === null || !in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) => 'the invoice no longer needs payment',
                $payments->hasPendingProof((int) $invoice->id) => 'another payment for this invoice is awaiting verification',
                (int) round($payments->getOutstanding($invoice) * 100) !== (int) round((float) $transaction->amount * 100) => 'the amount due changed',
                default => null,
            };
            if ($reason !== null) {
                $transaction->updateAttributes(['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => 'Not taken: ' . $reason . '.', 'updated_at' => time()]);
            }
            $dbTransaction->commit();

            return $reason !== null ? $transaction : null;
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            throw $e;
        }
    }

    private function transaction(string $orderId): Transaction
    {
        $transaction = Transaction::findOne(['gateway_order_id' => $orderId, 'gateway_id' => Transaction::GATEWAY_PAYPAL]);
        if ($transaction === null) {
            throw new \DomainException('Payment transaction does not exist.');
        }

        return $transaction;
    }

    private function alertNotRecorded(Transaction $transaction, string $reason): void
    {
        Yii::error(['message' => 'PayPal payment found but not recorded.', 'orderId' => $transaction->gateway_order_id, 'error' => $reason], __METHOD__);
        try {
            $payload = json_decode((string) $transaction->raw_payload, true);
            $reference = (string) ($transaction->gateway_payment_id ?: $transaction->gateway_order_id);
            \common\services\admin\AdminAlertService::paymentNotRecorded((string) $transaction->gateway_order_id, $reference, (float) $transaction->amount, $reason . (is_array($payload) && isset($payload['usd']) ? ' (US$' . $payload['usd'] . ')' : ''), 'PayPal');
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Could not alert admins about a PayPal payment.', 'orderId' => $transaction->gateway_order_id, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    /** @return Transaction[] unpaid orders (pending, or superseded by a coupon) created in the last $maxAge seconds */
    private function unpaidOrders(array $condition, int $maxAge): array
    {
        return Transaction::find()
            ->where(['gateway_id' => Transaction::GATEWAY_PAYPAL, 'gateway_payment_id' => null])
            ->andWhere(['or', ['status' => Transaction::STATUS_PENDING], ['status' => Transaction::STATUS_FAILED, 'user_payment_notes' => RazorpayService::SUPERSEDED_NOTE]])
            ->andWhere(['>=', 'created_at', time() - $maxAge])
            ->andWhere($condition)
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /** The order as PayPal reports it; null when PayPal no longer has it (expired). */
    private function fetchOrder(string $orderId): ?array
    {
        $response = $this->api()->get('v2/checkout/orders/' . rawurlencode($orderId))->send();
        if ((int) $response->statusCode === 404) {
            return null;
        }
        if (!$response->isOk || !is_array($response->data)) {
            throw new \RuntimeException('PayPal did not return order ' . $orderId . ' (HTTP ' . $response->statusCode . ').');
        }

        return $response->data;
    }

    private function api(): Client
    {
        return new Client([
            'baseUrl' => $this->isSandbox() ? self::SANDBOX_URL : self::LIVE_URL,
            'transport' => $this->_transport ?? StreamTransport::class,
            'requestConfig' => [
                'headers' => ['Authorization' => 'Bearer ' . $this->token()],
                'options' => ['timeout' => 20, 'sslVerifyPeer' => true],
            ],
        ]);
    }

    private function token(): string
    {
        if ($this->_token !== null) {
            return $this->_token;
        }
        $client = new Client(['baseUrl' => $this->isSandbox() ? self::SANDBOX_URL : self::LIVE_URL, 'transport' => $this->_transport ?? StreamTransport::class]);
        /** @var Response $response */
        $response = $client->post('v1/oauth2/token', ['grant_type' => 'client_credentials'], [
            'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ':' . ($this->_config['secret'] ?? '')),
        ])->setOptions(['timeout' => 20, 'sslVerifyPeer' => true])->send();
        $token = $response->isOk ? ($response->data['access_token'] ?? null) : null;
        if (!is_string($token) || $token === '') {
            Yii::error(['message' => 'PayPal sign-in failed: check PAYPAL_CLIENT_ID, PAYPAL_SECRET and PAYPAL_MODE.', 'status' => $response->statusCode], __METHOD__);
            throw new \RuntimeException('PayPal could not be reached. Please try again shortly.');
        }

        return $this->_token = $token;
    }
}
