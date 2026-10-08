<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "invoice".
 *
 * @property int $id
 * @property int $user_id
 * @property string $invoice_number
 * @property string $client_name
 * @property string|null $client_address_line_1
 * @property string|null $client_address_line_2
 * @property float $subtotal_amount Sum of all invoice items before taxes and discounts
 * @property int|null $coupon_id Maintains historical link back to coupons reference table if available
 * @property string|null $coupon_code Preserves coupon name snapshot text at point-of-sale for audit permanence
 * @property float $discount_amount Total mathematical deduction applied via code or flat reduction adjustment
 * @property float|null $cgst_percent
 * @property float|null $sgst_percent
 * @property float|null $igst_percent
 * @property float $total_payable Calculated formula: (Subtotal - Discount) + Taxes applied
 * @property string $status
 * @property string|null $due_date
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Coupons $coupon
 * @property InvoiceItem[] $invoiceItems
 * @property ServiceRenewalHistory[] $serviceRenewalHistories
 * @property Transaction[] $transactions
 * @property User $user
 */
class Invoice extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_UNPAID = 'Unpaid';
    const STATUS_PAID = 'Paid';
    const STATUS_PARTIALLY_PAID = 'Partially_Paid';
    const STATUS_CANCELLED = 'Cancelled';
    const STATUS_REFUNDED = 'Refunded';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['client_address_line_1', 'client_address_line_2', 'coupon_id', 'coupon_code', 'due_date'], 'default', 'value' => null],
            [['total_payable'], 'default', 'value' => 0.00],
            [['sgst_percent'], 'default', 'value' => 9.00],
            [['status'], 'default', 'value' => 'Unpaid'],
            [['user_id', 'invoice_number', 'client_name', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'coupon_id', 'created_at', 'updated_at'], 'integer'],
            [['subtotal_amount', 'discount_amount', 'cgst_percent', 'sgst_percent', 'igst_percent', 'total_payable'], 'number'],
            [['status'], 'string'],
            [['due_date'], 'safe'],
            [['invoice_number'], 'string', 'max' => 100],
            [['client_name', 'client_address_line_1', 'client_address_line_2'], 'string', 'max' => 255],
            [['coupon_code'], 'string', 'max' => 32],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['coupon_id'], 'exist', 'skipOnError' => true, 'targetClass' => Coupons::class, 'targetAttribute' => ['coupon_id' => 'coupon_id']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'invoice_number' => 'Invoice Number',
            'client_name' => 'Client Name',
            'client_address_line_1' => 'Client Address Line 1',
            'client_address_line_2' => 'Client Address Line 2',
            'subtotal_amount' => 'Subtotal Amount',
            'coupon_id' => 'Coupon ID',
            'coupon_code' => 'Coupon Code',
            'discount_amount' => 'Discount Amount',
            'cgst_percent' => 'Cgst Percent',
            'sgst_percent' => 'Sgst Percent',
            'igst_percent' => 'Igst Percent',
            'total_payable' => 'Total Payable',
            'status' => 'Status',
            'due_date' => 'Due Date',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Coupon]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoupon()
    {
        return $this->hasOne(Coupons::class, ['coupon_id' => 'coupon_id']);
    }

    /**
     * Gets query for [[InvoiceItems]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, ['invoice_id' => 'id']);
    }

    /**
     * Gets query for [[ServiceRenewalHistories]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServiceRenewalHistories()
    {
        return $this->hasMany(ServiceRenewalHistory::class, ['invoice_id' => 'id']);
    }

    /**
     * Gets query for [[Transactions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransactions()
    {
        return $this->hasMany(Transaction::class, ['invoice_id' => 'id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }


    /**
     * column status ENUM value labels
     * @return string[]
     */
    public static function optsStatus()
    {
        return [
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PARTIALLY_PAID => 'Partially_Paid',
            self::STATUS_CANCELLED => 'Cancelled',
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
    public function isStatusUnpaid()
    {
        return $this->status === self::STATUS_UNPAID;
    }

    public function setStatusToUnpaid()
    {
        $this->status = self::STATUS_UNPAID;
    }

    /**
     * @return bool
     */
    public function isStatusPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function setStatusToPaid()
    {
        $this->status = self::STATUS_PAID;
    }

    /**
     * @return bool
     */
    public function isStatusPartiallypaid()
    {
        return $this->status === self::STATUS_PARTIALLY_PAID;
    }

    public function setStatusToPartiallypaid()
    {
        $this->status = self::STATUS_PARTIALLY_PAID;
    }

    /**
     * @return bool
     */
    public function isStatusCancelled()
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function setStatusToCancelled()
    {
        $this->status = self::STATUS_CANCELLED;
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
