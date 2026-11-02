<?php

declare(strict_types=1);

namespace common\services\payment;

use common\models\Invoice;
use common\models\Transaction;
use common\services\admin\AdminAlertService;
use common\services\billing\PaymentConfirmationService;
use common\services\billing\PaymentProcessingService;
use Yii;
use yii\httpclient\Client;

/**
 * Razorpay Standard Checkout: order creation, signature checks and capture.
 *
 * Configured from .env through params['razorpay']:
 * RAZORPAY_ENABLED, RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET, RAZORPAY_WEBHOOK_SECRET.
 *
 * A payment is captured from two independent signals, whichever arrives first:
 * the browser callback (checkout signature) and the payment.captured webhook.
 * capture() is idempotent, so the second one is a no-op.
 */
final class RazorpayService
{
    private const API_URL = 'https://api.razorpay.com/v1/';

    private array $_config;

    public function __construct(?array $config = null)
    {
        $this->_config = $config ?? (Yii::$app->params['razorpay'] ?? []);
    }

    public function isEnabled(): bool
    {
        return !empty($this->_config['enabled'])
            && ($this->_config['keyId'] ?? '') !== ''
            && ($this->_config['keySecret'] ?? '') !== '';
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
            ->orderBy(['id' => SORT_DESC])
            ->one();
        if ($existing !== null && $this->toMinor((float) $existing->amount) === $this->toMinor($amount)) {
            return $existing;
        }

        $response = (new Client(['baseUrl' => self::API_URL]))
            ->post('orders', [
                'amount' => $this->toMinor($amount),
                'currency' => 'INR',
                'receipt' => substr((string) $invoice->invoice_number, 0, 40),
                'notes' => ['invoice_number' => (string) $invoice->invoice_number],
            ])
            ->setFormat(Client::FORMAT_JSON)
            ->addHeaders(['Authorization' => 'Basic ' . base64_encode($this->_config['keyId'] . ':' . $this->_config['keySecret'])])
            ->setOptions(['timeout' => 20])
            ->send();

        $orderId = $response->isOk ? ($response->data['id'] ?? null) : null;
        if (!is_string($orderId) || $orderId === '') {
            Yii::error(['message' => 'Razorpay order creation failed.', 'invoice' => $invoice->invoice_number, 'status' => $response->statusCode, 'body' => $response->content], __METHOD__);
            throw new \RuntimeException('Could not start online payment. Please try again.');
        }

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

    /** Signature returned to the browser by Checkout: HMAC(order_id|payment_id, key_secret). */
    public function verifyCheckoutSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, (string) ($this->_config['keySecret'] ?? ''));
        return $signature !== '' && hash_equals($expected, $signature);
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
     * @throws \DomainException when the payment does not match a pending order
     */
    public function capture(string $orderId, string $paymentId, ?int $amountMinor = null): Transaction
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
            if ($transaction->status === Transaction::STATUS_CAPTURED) {
                if ($transaction->gateway_payment_id !== $paymentId) {
                    throw new \DomainException('Captured payment reference does not match.');
                }
                $dbTransaction->commit();
                return $transaction;
            }
            if ($transaction->status !== Transaction::STATUS_PENDING) {
                throw new \DomainException('Payment transaction is not pending.');
            }
            if ($amountMinor !== null && $this->toMinor((float) $transaction->amount) !== $amountMinor) {
                throw new \DomainException('Payment amount does not match.');
            }

            $now = time();
            $transaction->gateway_payment_id = $paymentId;
            $transaction->status = Transaction::STATUS_CAPTURED;
            $transaction->paid_at = $now;
            $transaction->updated_at = $now;

            // The customer has been charged, so the capture is always recorded. If the
            // invoice can no longer take payments it is left unapplied for an admin.
            $invoice = Invoice::findOne((int) $transaction->invoice_id);
            $unapplied = $invoice !== null && in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_CARRIED_FORWARD], true);
            if ($unapplied) {
                $transaction->user_payment_notes = trim($transaction->user_payment_notes . "\nUNAPPLIED: invoice was {$invoice->status} when paid. Refund in Razorpay or apply manually.");
            }
            if (!$transaction->save()) {
                throw new \RuntimeException('Captured payment could not be saved.');
            }
            if (!$unapplied) {
                (new PaymentProcessingService())->transactionChanged($transaction, false);
            }
            $dbTransaction->commit();
            if ($unapplied) {
                Yii::warning(['message' => 'Razorpay payment captured on a non-payable invoice.', 'transactionId' => (int) $transaction->id, 'invoice' => $invoice->invoice_number, 'status' => $invoice->status], __METHOD__);
                AdminAlertService::paymentUnapplied($invoice->invoice_number, (string) $invoice->status, (float) $transaction->amount, $paymentId);
                return $transaction;
            }
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            throw $e;
        }

        try {
            (new PaymentConfirmationService())->send($transaction);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Razorpay payment was captured but confirmation email failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
        }

        return $transaction;
    }

    private function toMinor(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
