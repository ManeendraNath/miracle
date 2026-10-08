<?php

namespace common\models;

use Yii;
use yii\web\UploadedFile;

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
    /**
     * @var UploadedFile
     */
    public $receiptFile; // Form helper attribute placeholder

    public function rules()
    {
        return array_merge(parent::rules(), [
            // Safe assignment for custom customer notes
            [['user_payment_notes'], 'string'],
            // Secure validation parameters for uploaded images or PDF billing files
            [['receiptFile'], 'file', 'skipOnEmpty' => true, 'extensions' => 'png, jpg, jpeg, pdf', 'maxSize' => 1024 * 1024 * 5, 'message' => 'Receipt file must be an image or PDF under 5MB.'],
        ]);
    }

    /**
     * Handles safe server directory migration operations for transaction receipts.
     */
    public function uploadReceipt()
    {
        if ($this->receiptFile instanceof UploadedFile && $this->validate(['receiptFile'])) {
            // Secure isolated naming mapping to prevent directory injection tracking bugs
            $directory = Yii::getAlias('@backend/web/uploads/receipts/');
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
            
            $fileName = 'receipt_' . $this->invoice_id . '_' . time() . '.' . $this->receiptFile->extension;
            if ($this->receiptFile->saveAs($directory . $fileName)) {
                $this->payment_receipt_file = 'uploads/receipts/' . $fileName;
                return true;
            }
        }
        return false;
    }
}

