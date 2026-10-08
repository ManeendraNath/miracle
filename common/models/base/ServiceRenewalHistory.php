<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "service_renewal_history".
 *
 * @property int $id
 * @property string $item_type
 * @property int $item_id The Primary Key ID of the corresponding domain, hosting, or AMC record
 * @property int|null $invoice_id Link to billing record if applicable
 * @property int|null $transaction_id Link to transaction log gateway reference
 * @property float $billed_amount The exact rate charged for this specific year extension
 * @property int $renewal_period_months Usually 12 months, or custom adjustments
 * @property string $start_date Cycle coverage commencement date
 * @property string $end_date Calculated new expiry timestamp threshold
 * @property int $logged_at Timestamp of payment verification execution
 *
 * @property Invoice $invoice
 * @property Transaction $transaction
 */
class ServiceRenewalHistory extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const ITEM_TYPE_DOMAIN = 'domain';
    const ITEM_TYPE_HOSTING = 'hosting';
    const ITEM_TYPE_MAINTENANCE = 'maintenance';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'service_renewal_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['invoice_id', 'transaction_id'], 'default', 'value' => null],
            [['renewal_period_months'], 'default', 'value' => 12],
            [['item_type', 'item_id', 'billed_amount', 'start_date', 'end_date', 'logged_at'], 'required'],
            [['item_type'], 'string'],
            [['item_id', 'invoice_id', 'transaction_id', 'renewal_period_months', 'logged_at'], 'integer'],
            [['billed_amount'], 'number'],
            [['start_date', 'end_date'], 'safe'],
            ['item_type', 'in', 'range' => array_keys(self::optsItemType())],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
            [['transaction_id'], 'exist', 'skipOnError' => true, 'targetClass' => Transaction::class, 'targetAttribute' => ['transaction_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'item_type' => 'Item Type',
            'item_id' => 'Item ID',
            'invoice_id' => 'Invoice ID',
            'transaction_id' => 'Transaction ID',
            'billed_amount' => 'Billed Amount',
            'renewal_period_months' => 'Renewal Period Months',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'logged_at' => 'Logged At',
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
     * Gets query for [[Transaction]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTransaction()
    {
        return $this->hasOne(Transaction::class, ['id' => 'transaction_id']);
    }


    /**
     * column item_type ENUM value labels
     * @return string[]
     */
    public static function optsItemType()
    {
        return [
            self::ITEM_TYPE_DOMAIN => 'domain',
            self::ITEM_TYPE_HOSTING => 'hosting',
            self::ITEM_TYPE_MAINTENANCE => 'maintenance',
        ];
    }

    /**
     * @return string
     */
    public function displayItemType()
    {
        return self::optsItemType()[$this->item_type];
    }

    /**
     * @return bool
     */
    public function isItemTypeDomain()
    {
        return $this->item_type === self::ITEM_TYPE_DOMAIN;
    }

    public function setItemTypeToDomain()
    {
        $this->item_type = self::ITEM_TYPE_DOMAIN;
    }

    /**
     * @return bool
     */
    public function isItemTypeHosting()
    {
        return $this->item_type === self::ITEM_TYPE_HOSTING;
    }

    public function setItemTypeToHosting()
    {
        $this->item_type = self::ITEM_TYPE_HOSTING;
    }

    /**
     * @return bool
     */
    public function isItemTypeMaintenance()
    {
        return $this->item_type === self::ITEM_TYPE_MAINTENANCE;
    }

    public function setItemTypeToMaintenance()
    {
        $this->item_type = self::ITEM_TYPE_MAINTENANCE;
    }
}
