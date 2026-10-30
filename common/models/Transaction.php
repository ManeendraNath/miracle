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
 */
class Transaction extends base\Transaction
{

    /** gateway_id values (see column comment). */
    public const GATEWAY_MANUAL = 0;
    public const GATEWAY_RAZORPAY = 1;
    public const GATEWAY_UPI_BANK = 2;
    public function rules()
    {
        return array_merge(parent::rules(), [
            // Safe assignment for custom customer notes
            [['user_payment_notes'], 'string'],
        ]);
    }

}

