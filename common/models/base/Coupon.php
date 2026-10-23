<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "coupon".
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property string $code
 * @property float $amount
 * @property float $minimum_order
 * @property string|null $start_date
 * @property string|null $end_date
 * @property int $uses_per_coupon
 * @property int $uses_per_user
 * @property string $is_active
 * @property int $created_at
 * @property int $updated_at
 *
 * @property CouponApplicability $couponApplicability
 * @property CouponAssignedUser $couponAssignedUser
 * @property CouponRedemption[] $couponRedemptions
 * @property Invoice[] $invoices
 */
class Coupon extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TYPE_F = 'F';
    const TYPE_P = 'P';
    const IS_ACTIVE_Y = 'Y';
    const IS_ACTIVE_N = 'N';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['description', 'start_date', 'end_date'], 'default', 'value' => null],
            [['type'], 'default', 'value' => 'F'],
            [['minimum_order'], 'default', 'value' => 0.00],
            [['uses_per_coupon'], 'default', 'value' => 1],
            [['uses_per_user'], 'default', 'value' => 0],
            [['is_active'], 'default', 'value' => 'Y'],
            [['name', 'code', 'created_at', 'updated_at'], 'required'],
            [['description', 'type', 'is_active'], 'string'],
            [['amount', 'minimum_order'], 'number'],
            [['start_date', 'end_date'], 'safe'],
            [['uses_per_coupon', 'uses_per_user', 'created_at', 'updated_at'], 'integer'],
            [['name', 'code'], 'string', 'max' => 32],
            ['type', 'in', 'range' => array_keys(self::optsType())],
            ['is_active', 'in', 'range' => array_keys(self::optsIsActive())],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'description' => 'Description',
            'type' => 'Type',
            'code' => 'Code',
            'amount' => 'Amount',
            'minimum_order' => 'Minimum Order',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'uses_per_coupon' => 'Uses Per Coupon',
            'uses_per_user' => 'Uses Per User',
            'is_active' => 'Is Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[CouponApplicability]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponApplicability()
    {
        return $this->hasOne(CouponApplicability::class, ['coupon_id' => 'id']);
    }

    /**
     * Gets query for [[CouponAssignedUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponAssignedUser()
    {
        return $this->hasOne(CouponAssignedUser::class, ['coupon_id' => 'id']);
    }

    /**
     * Gets query for [[CouponRedemptions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCouponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class, ['coupon_id' => 'id']);
    }

    /**
     * Gets query for [[Invoices]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvoices()
    {
        return $this->hasMany(Invoice::class, ['coupon_id' => 'id']);
    }


    /**
     * column type ENUM value labels
     * @return string[]
     */
    public static function optsType()
    {
        return [
            self::TYPE_F => 'F',
            self::TYPE_P => 'P',
        ];
    }

    /**
     * column is_active ENUM value labels
     * @return string[]
     */
    public static function optsIsActive()
    {
        return [
            self::IS_ACTIVE_Y => 'Y',
            self::IS_ACTIVE_N => 'N',
        ];
    }

    /**
     * @return string
     */
    public function displayType()
    {
        return self::optsType()[$this->type];
    }

    /**
     * @return bool
     */
    public function isTypeF()
    {
        return $this->type === self::TYPE_F;
    }

    public function setTypeToF()
    {
        $this->type = self::TYPE_F;
    }

    /**
     * @return bool
     */
    public function isTypeP()
    {
        return $this->type === self::TYPE_P;
    }

    public function setTypeToP()
    {
        $this->type = self::TYPE_P;
    }

    /**
     * @return string
     */
    public function displayIsActive()
    {
        return self::optsIsActive()[$this->is_active];
    }

    /**
     * @return bool
     */
    public function isIsActiveY()
    {
        return $this->is_active === self::IS_ACTIVE_Y;
    }

    public function setIsActiveToY()
    {
        $this->is_active = self::IS_ACTIVE_Y;
    }

    /**
     * @return bool
     */
    public function isIsActiveN()
    {
        return $this->is_active === self::IS_ACTIVE_N;
    }

    public function setIsActiveToN()
    {
        $this->is_active = self::IS_ACTIVE_N;
    }
}
