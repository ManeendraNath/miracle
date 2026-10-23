<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "coupon_assigned_user".
 *
 * @property int $id
 * @property int $coupon_id
 * @property int $user_id
 * @property int $assigned_at
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Coupon $coupon
 * @property User $user
 */
class CouponAssignedUser extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon_assigned_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_id', 'user_id', 'assigned_at', 'created_at', 'updated_at'], 'required'],
            [['coupon_id', 'user_id', 'assigned_at', 'created_at', 'updated_at'], 'integer'],
            [['coupon_id'], 'unique'],
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
            'coupon_id' => 'Coupon ID',
            'user_id' => 'User ID',
            'assigned_at' => 'Assigned At',
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
