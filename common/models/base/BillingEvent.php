<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "billing_event".
 *
 * @property int $id
 * @property int|null $billing_run_id
 * @property int $user_id
 * @property int $item_type
 * @property int $item_id
 * @property string $description
 * @property string $period_start
 * @property string $period_end
 * @property float|null $previous_amount
 * @property float $increase_percent
 * @property float $calculated_amount
 * @property float $amount
 * @property int $is_manual_amount
 * @property string $due_date
 * @property int|null $invoice_id
 * @property string $status
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 *
 * @property BillingRun $billingRun
 * @property Invoice $invoice
 * @property InvoiceItem $invoiceItem
 * @property ServiceRenewalHistory $serviceRenewalHistory
 * @property User $user
 */
class BillingEvent extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const STATUS_PENDING = 'Pending';
    const STATUS_INVOICED = 'Invoiced';
    const STATUS_FULFILLED = 'Fulfilled';
    const STATUS_CANCELLED = 'Cancelled';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'billing_event';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['billing_run_id', 'previous_amount', 'invoice_id', 'notes'], 'default', 'value' => null],
            [['increase_percent'], 'default', 'value' => 0.00],
            [['is_manual_amount'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => 'Pending'],
            [['billing_run_id', 'user_id', 'item_type', 'item_id', 'is_manual_amount', 'invoice_id', 'created_at', 'updated_at'], 'integer'],
            [['user_id', 'item_type', 'item_id', 'description', 'period_start', 'period_end', 'calculated_amount', 'amount', 'due_date', 'created_at', 'updated_at'], 'required'],
            [['period_start', 'period_end', 'due_date'], 'safe'],
            [['previous_amount', 'increase_percent', 'calculated_amount', 'amount'], 'number'],
            [['status'], 'string'],
            [['description', 'notes'], 'string', 'max' => 500],
            ['status', 'in', 'range' => array_keys(self::optsStatus())],
            [['item_type', 'item_id', 'period_start', 'period_end'], 'unique', 'targetAttribute' => ['item_type', 'item_id', 'period_start', 'period_end']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
            [['billing_run_id'], 'exist', 'skipOnError' => true, 'targetClass' => BillingRun::class, 'targetAttribute' => ['billing_run_id' => 'id']],
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
            'billing_run_id' => 'Billing Run ID',
            'user_id' => 'User ID',
            'item_type' => 'Item Type',
            'item_id' => 'Item ID',
            'description' => 'Description',
            'period_start' => 'Period Start',
            'period_end' => 'Period End',
            'previous_amount' => 'Previous Amount',
            'increase_percent' => 'Increase Percent',
            'calculated_amount' => 'Calculated Amount',
            'amount' => 'Amount',
            'is_manual_amount' => 'Is Manual Amount',
            'due_date' => 'Due Date',
            'invoice_id' => 'Invoice ID',
            'status' => 'Status',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
    }

    /**
     * Gets query for [[InvoiceItem]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoiceItem()
    {
        return $this->hasOne(InvoiceItem::class, ['billing_event_id' => 'id']);
    }

    /**
     * Gets query for [[ServiceRenewalHistory]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getServiceRenewalHistory()
    {
        return $this->hasOne(ServiceRenewalHistory::class, ['billing_event_id' => 'id']);
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
            self::STATUS_PENDING => 'Pending',
            self::STATUS_INVOICED => 'Invoiced',
            self::STATUS_FULFILLED => 'Fulfilled',
            self::STATUS_CANCELLED => 'Cancelled',
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
    public function isStatusInvoiced()
    {
        return $this->status === self::STATUS_INVOICED;
    }

    public function setStatusToInvoiced()
    {
        $this->status = self::STATUS_INVOICED;
    }

    /**
     * @return bool
     */
    public function isStatusFulfilled()
    {
        return $this->status === self::STATUS_FULFILLED;
    }

    public function setStatusToFulfilled()
    {
        $this->status = self::STATUS_FULFILLED;
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
}
