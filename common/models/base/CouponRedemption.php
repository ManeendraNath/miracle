<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "coupon_redemption".
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property int $invoice_id
 * @property string $coupon_code
 * @property float $discount_amount
 * @property int $redeemed_at
 *
 * @property Coupon $coupon
 * @property Invoice $invoice
 * @property User $user
 */
class CouponRedemption extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon_redemption';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['discount_amount'], 'default', 'value' => 0.00],
            [['coupon_id', 'user_id', 'invoice_id', 'coupon_code', 'redeemed_at'], 'required'],
            [['coupon_id', 'user_id', 'invoice_id', 'redeemed_at'], 'integer'],
            [['discount_amount'], 'number'],
            [['coupon_code'], 'string', 'max' => 32],
            [['invoice_id'], 'unique'],
            [['coupon_id'], 'exist', 'skipOnError' => true, 'targetClass' => Coupon::class, 'targetAttribute' => ['coupon_id' => 'id']],
            [['invoice_id'], 'exist', 'skipOnError' => true, 'targetClass' => Invoice::class, 'targetAttribute' => ['invoice_id' => 'id']],
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
            'coupon_id' => 'Coupon ID',
            'user_id' => 'User ID',
            'invoice_id' => 'Invoice ID',
            'coupon_code' => 'Coupon Code',
            'discount_amount' => 'Discount Amount',
            'redeemed_at' => 'Redeemed At',
        ];
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
     * Gets query for [[Invoice]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoice()
    {
        return $this->hasOne(Invoice::class, ['id' => 'invoice_id']);
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
