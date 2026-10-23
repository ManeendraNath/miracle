<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "coupon_applicability".
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $item_type
 * @property int|null $item_id
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Coupon $coupon
 */
class CouponApplicability extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon_applicability';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['item_id'], 'default', 'value' => null],
            [['coupon_id', 'item_type', 'created_at', 'updated_at'], 'required'],
            [['coupon_id', 'item_type', 'item_id', 'created_at', 'updated_at'], 'integer'],
            [['coupon_id'], 'unique'],
            [['coupon_id'], 'exist', 'skipOnError' => true, 'targetClass' => Coupon::class, 'targetAttribute' => ['coupon_id' => 'id']],
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
            'item_type' => 'Item Type',
            'item_id' => 'Item ID',
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
        return $this->hasOne(Coupon::class, ['id' => 'coupon_id']);
    }

}
