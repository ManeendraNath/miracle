<?php

namespace common\models\base;

use Yii;

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
class Transaction extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_PENDING = 'Pending';
    const STATUS_AUTHORIZED = 'Authorized';
    const STATUS_CAPTURED = 'Captured';
    const STATUS_FAILED = 'Failed';
    const STATUS_REFUNDED = 'Refunded';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['gateway_id', 'gateway_payment_id', 'gateway_order_id', 'user_payment_notes', 'payment_receipt_file', 'raw_payload', 'paid_at'], 'default', 'value' => null],
            [['gateway_fee'], 'default', 'value' => 0.00],
            [['status'], 'default', 'value' => 'Pending'],
            [['invoice_id', 'amount', 'created_at', 'updated_at'], 'required'],
            [['invoice_id', 'gateway_id', 'paid_at', 'created_at', 'updated_at'], 'integer'],
            [['amount', 'gateway_fee'], 'number'],
            [['status', 'user_payment_notes', 'raw_payload'], 'string'],
            [['gateway_payment_id', 'gateway_order_id'], 'string', 'max' => 100],
            [['payment_receipt_file'], 'string', 'max' => 255],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice_id' => 'Invoice ID',
            'gateway_id' => 'Gateway ID',
            'gateway_payment_id' => 'Gateway Payment ID',
            'gateway_order_id' => 'Gateway Order ID',
            'amount' => 'Amount',
            'gateway_fee' => 'Gateway Fee',
            'status' => 'Status',
            'user_payment_notes' => 'User Payment Notes',
            'payment_receipt_file' => 'Payment Receipt File',
            'raw_payload' => 'Raw Payload',
            'paid_at' => 'Paid At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

    /**
     * Gets query for [[ServiceRenewalHistories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServiceRenewalHistories()
    {
        return $this->hasMany(ServiceRenewalHistory::class, ['transaction_id' => 'id']);
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_AUTHORIZED => 'Authorized',
            self::STATUS_CAPTURED => 'Captured',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }

    /**
     * @return string
     */
    public function displayStatus()
    {
        return self::optsStatus()[$this->status];
    }

    /**
     * @return bool
     */
    public function isStatusPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function setStatusToPending()
    {
        $this->status = self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isStatusAuthorized()
    {
        return $this->status === self::STATUS_AUTHORIZED;
    }

    public function setStatusToAuthorized()
    {
        $this->status = self::STATUS_AUTHORIZED;
    }

    /**
     * @return bool
     */
    public function isStatusCaptured()
    {
        return $this->status === self::STATUS_CAPTURED;
    }

    public function setStatusToCaptured()
    {
        $this->status = self::STATUS_CAPTURED;
    }

    /**
     * @return bool
     */
    public function isStatusFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function setStatusToFailed()
    {
        $this->status = self::STATUS_FAILED;
    }

    /**
     * @return bool
     */
    public function isStatusRefunded()
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function setStatusToRefunded()
    {
        $this->status = self::STATUS_REFUNDED;
    }
}
