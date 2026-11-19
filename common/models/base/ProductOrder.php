<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "product_order".
 *
 * @property int $id
 * @property int $user_id
 * @property int $product_id
 * @property string $licence
 * @property int $invoice_id
 * @property string|null $licence_key
 * @property int|null $paid_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Invoice $invoice
 * @property Product $product
 * @property User $user
 */
class ProductOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['licence_key', 'paid_at'], 'default', 'value' => null],
            [['user_id', 'product_id', 'licence', 'invoice_id', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'product_id', 'invoice_id', 'paid_at', 'created_at', 'updated_at'], 'integer'],
            [['licence'], 'string', 'max' => 10],
            [['licence_key'], 'string', 'max' => 32],
            [['invoice_id'], 'unique'],
            [['licence_key'], 'unique'],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Product::class, 'targetAttribute' => ['product_id' => 'id']],
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
            'product_id' => 'Product ID',
            'licence' => 'Licence',
            'invoice_id' => 'Invoice ID',
            'licence_key' => 'Licence Key',
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
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
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
}
