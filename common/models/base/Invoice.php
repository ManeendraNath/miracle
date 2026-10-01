<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "invoice".
 *
 * @property int $id
 * @property string $invoice_number
 * @property string $client_name
 * @property string|null $client_address_line_1
 * @property string|null $client_address_line_2
 * @property float|null $discount_amount
 * @property float|null $cgst_percent
 * @property float|null $sgst_percent
 * @property float|null $igst_percent
 * @property string|null $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property InvoiceItem[] $invoiceItems
 */
class Invoice extends \yii\db\ActiveRecord
{


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
            [['client_address_line_1', 'client_address_line_2'], 'default', 'value' => null],
            [['igst_percent'], 'default', 'value' => 0.00],
            [['sgst_percent'], 'default', 'value' => 9.00],
            [['status'], 'default', 'value' => 'Unpaid'],
            [['invoice_number', 'client_name', 'created_at', 'updated_at'], 'required'],
            [['discount_amount', 'cgst_percent', 'sgst_percent', 'igst_percent'], 'number'],
            [['created_at', 'updated_at'], 'integer'],
            [['invoice_number'], 'string', 'max' => 100],
            [['client_name', 'client_address_line_1', 'client_address_line_2'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 50],
            [['invoice_number'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'invoice_number' => 'Invoice Number',
            'client_name' => 'Client Name',
            'client_address_line_1' => 'Client Address Line 1',
            'client_address_line_2' => 'Client Address Line 2',
            'discount_amount' => 'Discount Amount',
            'cgst_percent' => 'Cgst Percent',
            'sgst_percent' => 'Sgst Percent',
            'igst_percent' => 'Igst Percent',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
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

}
