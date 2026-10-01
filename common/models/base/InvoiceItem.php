<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "invoice_item".
 *
 * @property int $id
 * @property int $invoice_id
 * @property string $category
 * @property string $description
 * @property string|null $duration_quantity
 * @property float $unit_price
 * @property float $total_price
 *
 * @property Invoice $invoice
 */
class InvoiceItem extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'invoice_item';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['duration_quantity'], 'default', 'value' => '1 Year'],
            [['invoice_id', 'category', 'description', 'unit_price', 'total_price'], 'required'],
            [['invoice_id'], 'integer'],
            [['description'], 'string'],
            [['unit_price', 'total_price'], 'number'],
            [['category', 'duration_quantity'], 'string', 'max' => 100],
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
            'category' => 'Category',
            'description' => 'Description',
            'duration_quantity' => 'Duration Quantity',
            'unit_price' => 'Unit Price',
            'total_price' => 'Total Price',
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

}
