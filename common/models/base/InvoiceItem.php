<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "invoice_item".
 *
 * @property int $id
 * @property int $invoice_id
 * @property int|null $item_type 1 : domain, 2 :hosting, 3 : maintenance, 4 : custom
 * @property int|null $item_id Maps to the primary key of the domain/hosting/maintenance assets
 * @property string $category
 * @property string $description
 * @property int $quantity
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
            [['item_id'], 'default', 'value' => null],
            [['item_type'], 'default', 'value' => 4],
            [['quantity'], 'default', 'value' => 1],
            [['invoice_id', 'category', 'description', 'unit_price', 'total_price'], 'required'],
            [['invoice_id', 'item_type', 'item_id', 'quantity'], 'integer'],
            [['description'], 'string'],
            [['unit_price', 'total_price'], 'number'],
            [['category'], 'string', 'max' => 100],
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
            'item_type' => 'Item Type',
            'item_id' => 'Item ID',
            'category' => 'Category',
            'description' => 'Description',
            'quantity' => 'Quantity',
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
