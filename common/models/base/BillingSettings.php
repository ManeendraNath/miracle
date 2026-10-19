<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "billing_settings".
 *
 * @property int $id
 * @property int $service_type
 * @property int $renewal_cycle_months
 * @property float $renewal_increase_percent
 * @property int $invoice_before_days
 * @property int $payment_due_days
 * @property int $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class BillingSettings extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'billing_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['renewal_increase_percent'], 'default', 'value' => 0.00],
            [['invoice_before_days'], 'default', 'value' => 30],
            [['payment_due_days'], 'default', 'value' => 15],
            [['is_active'], 'default', 'value' => 1],
            [['service_type', 'renewal_cycle_months', 'created_at', 'updated_at'], 'required'],
            [['service_type', 'renewal_cycle_months', 'invoice_before_days', 'payment_due_days', 'is_active', 'created_at', 'updated_at'], 'integer'],
            [['renewal_increase_percent'], 'number'],
            [['service_type'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_type' => 'Service Type',
            'renewal_cycle_months' => 'Renewal Cycle Months',
            'renewal_increase_percent' => 'Renewal Increase Percent',
            'invoice_before_days' => 'Invoice Before Days',
            'payment_due_days' => 'Payment Due Days',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

}
