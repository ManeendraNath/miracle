<?php

namespace common\models;

/**
 * This is the model class for table "transaction".
 *
 * @property int $id
 * @property int $invoice_id
 * @property int|null $gateway_id null/0: Manual, 1: Razorpay, 2: Personal UPI / Bank Deposit
 * @property string|null $gateway_payment_id Razorpay pay_id OR User-entered UPI Transaction/UTR Ref No
 * @property string|null $gateway_order_id Razorpay order_id reference token (null for Manual UPI)
 * @property float $amount
 * @property float|null $gateway_fee
 * @property string $status
 * @property string|null $user_payment_notes User remarks e.g., Sent from Rahul UPI wallet
 * @property string|null $payment_receipt_file File directory storage path path for uploaded snapshot proofs
 * @property string|null $raw_payload JSON dump for automated webhooks or error trace tracking
 * @property int|null $paid_at Timestamp of confirmation/capture check approval
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Invoice $invoice
 * @property ServiceRenewalHistory[] $serviceRenewalHistories
 * @property-read string $gatewayLabel
 */
class Transaction extends base\Transaction
{
    /** gateway_id values (see column comment). */
    public const GATEWAY_MANUAL = 0;
    public const GATEWAY_RAZORPAY = 1;
    public const GATEWAY_UPI_BANK = 2;
    public const GATEWAY_PAYPAL = 3;
    /** Checkout gateways: a Pending row is an order not paid yet, not a payment awaiting review. */
    public const ONLINE_GATEWAYS = [self::GATEWAY_RAZORPAY, self::GATEWAY_PAYPAL];
    /** Admin "record a payment" form: only these come from the form; status/paid_at/etc. go through approve/refund. */
    public const SCENARIO_ADMIN_ENTRY = 'admin-entry';
    /** Marks a Razorpay capture that could not be applied (invoice no longer payable). */
    public const UNAPPLIED_NOTE_PREFIX = 'UNAPPLIED:';
    /** Lines staff actions add to user_payment_notes. */
    private const STAFF_NOTE_PATTERN = '/^(UNAPPLIED:|Refunded ₹|Applied to invoice )/u';

    /**
     * gateway_id => label. A null gateway_id is Manual too.
     *
     * @return array<int, string>
     */
    public static function getGateways(): array
    {
        return [
            self::GATEWAY_MANUAL => 'Manual / Admin Credit',
            self::GATEWAY_RAZORPAY => 'Razorpay (Automated)',
            self::GATEWAY_UPI_BANK => 'Manual UPI / Bank Transfer',
            self::GATEWAY_PAYPAL => 'PayPal (Automated)',
        ];
    }

    public function getGatewayLabel(): string
    {
        return self::getGateways()[(int) $this->gateway_id] ?? self::getGateways()[self::GATEWAY_MANUAL];
    }

    /** Captured but kept off the invoice: an admin must refund or apply it. */
    /** The reference the customer gave: rejecting a proof prefixes it to free the unique value. */
    public function customerReference(): string
    {
        return (string) preg_replace('/^REJECTED-\d+-/', '', (string) $this->gateway_payment_id);
    }

    /** What a PayPal payer was charged, e.g. "US$41.18 at ₹85 per US$1"; null for any other payment. */
    public function paidInUsd(): ?string
    {
        $payload = (int) $this->gateway_id === self::GATEWAY_PAYPAL ? json_decode((string) $this->raw_payload, true) : null;
        if (!is_array($payload) || !isset($payload['usd'], $payload['rate'])) {
            return null;
        }

        return 'US$' . $payload['usd'] . ' at ₹' . rtrim(rtrim(number_format((float) $payload['rate'], 4, '.', ''), '0'), '.') . ' per US$1';
    }

    /** Paid through a checkout (Razorpay, PayPal), not a proof or a manual entry. */
    public function isOnline(): bool
    {
        return in_array((int) $this->gateway_id, self::ONLINE_GATEWAYS, true);
    }

    /** The status in the customer's words. */
    public function customerStatus(): string
    {
        return match (true) {
            $this->status === self::STATUS_CAPTURED => $this->isUnapplied() ? 'Not Applied' : 'Paid',
            $this->status === self::STATUS_FAILED && !$this->isOnline() => 'Rejected',
            default => (string) $this->status,
        };
    }

    /** What the customer wrote: the same column also collects staff notes they shouldn't be shown as theirs. */
    public function customerNotes(): string
    {
        return implode("\n", array_filter(explode("\n", (string) $this->user_payment_notes), static fn(string $line): bool => preg_match(self::STAFF_NOTE_PATTERN, $line) !== 1));
    }

    public function isUnapplied(): bool
    {
        return (bool) $this->is_unapplied;
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_ADMIN_ENTRY] = ['invoice_id', 'gateway_id', 'gateway_payment_id', 'amount', 'user_payment_notes'];

        return $scenarios;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Safe assignment for custom customer notes
            [['user_payment_notes'], 'string'],
            // A zero or negative "payment" would lower what the invoice counts as received.
            ['amount', 'number', 'min' => 0.01, 'tooSmall' => 'Amount must be greater than zero.'],
            ['gateway_id', 'in', 'range' => [self::GATEWAY_MANUAL, self::GATEWAY_UPI_BANK], 'on' => self::SCENARIO_ADMIN_ENTRY, 'message' => 'Online payments are recorded by Razorpay or PayPal, not by hand.'],
        ]);
    }
}
