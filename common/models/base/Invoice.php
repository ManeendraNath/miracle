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
 * @property int|null $emailed_at
 * @property int|null $billing_run_id
 * @property string|null $client_city
 * @property string|null $client_state
 * @property string|null $client_state_code
 * @property string|null $client_postal_code
 * @property string|null $client_gstin
 * @property string|null $client_pan
 * @property string|null $place_of_supply
 *
 * @property BillingEvent[] $billingEvents
 * @property BillingRun $billingRun
 * @property Coupon $coupon
 * @property CouponRedemption $couponRedemption
 * @property InvoiceItem[] $invoiceItems
 * @property InvoiceItem[] $invoiceItems0
 * @property InvoiceReminderLog[] $invoiceReminderLogs
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
    const STATUS_PARTIALLY_PAID = 'Partially_Paid';
    const STATUS_PAID = 'Paid';
    const STATUS_CARRIED_FORWARD = 'Carried_Forward';
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
            [['client_address_line_1', 'client_address_line_2', 'coupon_id', 'coupon_code', 'due_date', 'emailed_at', 'billing_run_id', 'client_city', 'client_state', 'client_state_code', 'client_postal_code', 'client_gstin', 'client_pan', 'place_of_supply'], 'default', 'value' => null],
            [['total_payable'], 'default', 'value' => 0.00],
            [['sgst_percent'], 'default', 'value' => 9.00],
            [['status'], 'default', 'value' => 'Unpaid'],
            [['user_id', 'invoice_number', 'client_name', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'coupon_id', 'created_at', 'updated_at', 'emailed_at', 'billing_run_id'], 'integer'],
            [['subtotal_amount', 'discount_amount', 'cgst_percent', 'sgst_percent', 'igst_percent', 'total_payable'], 'number'],
            [['status'], 'string'],
            [['due_date'], 'safe'],
            [['invoice_number', 'client_city', 'client_state', 'place_of_supply'], 'string', 'max' => 100],
            [['client_name', 'client_address_line_1', 'client_address_line_2'], 'string', 'max' => 255],
            [['coupon_code'], 'string', 'max' => 32],
            [['client_state_code', 'client_pan'], 'string', 'max' => 10],
            [['client_postal_code'], 'string', 'max' => 20],
            [['client_gstin'], 'string', 'max' => 15],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['invoice_number'], 'unique'],
            [['billing_run_id'], 'exist', 'skipOnError' => true, 'targetClass' => BillingRun::class, 'targetAttribute' => ['billing_run_id' => 'id']],
            [['coupon_id'], 'exist', 'skipOnError' => true, 'targetClass' => Coupon::class, 'targetAttribute' => ['coupon_id' => 'id']],
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
            'emailed_at' => 'Emailed At',
            'billing_run_id' => 'Billing Run ID',
            'client_city' => 'Client City',
            'client_state' => 'Client State',
            'client_state_code' => 'Client State Code',
            'client_postal_code' => 'Client Postal Code',
            'client_gstin' => 'Client Gstin',
            'client_pan' => 'Client Pan',
            'place_of_supply' => 'Place Of Supply',
        ];
    }

    /**
     * Gets query for [[BillingEvents]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBillingEvents()
    {
        return $this->hasMany(BillingEvent::class, ['invoice_id' => 'id']);
    }

    /**
     * Gets query for [[BillingRun]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getBillingRun()
    {
        return $this->hasOne(BillingRun::class, ['id' => 'billing_run_id']);
    }

    /**
     * Gets query for [[Coupon]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCoupon()
    {
        return $this->hasOne(Coupon::class, ['id' => 'coupon_id']);
    }

    /**
     * Gets query for [[CouponRedemption]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponRedemption()
    {
        return $this->hasOne(CouponRedemption::class, ['invoice_id' => 'id']);
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
     * Gets query for [[InvoiceItems0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItems0()
    {
        return $this->hasMany(InvoiceItem::class, ['source_invoice_id' => 'id']);
    }

    /**
     * Gets query for [[InvoiceReminderLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceReminderLogs()
    {
        return $this->hasMany(InvoiceReminderLog::class, ['invoice_id' => 'id']);
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
            self::STATUS_PARTIALLY_PAID => 'Partially_Paid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CARRIED_FORWARD => 'Carried_Forward',
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
    public function isStatusCarriedforward()
    {
        return $this->status === self::STATUS_CARRIED_FORWARD;
    }

    public function setStatusToCarriedforward()
    {
        $this->status = self::STATUS_CARRIED_FORWARD;
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
