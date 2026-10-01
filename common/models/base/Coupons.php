<?php

namespace common\models\base;

use Yii;

/**
 * This is the model class for table "coupons".
 *
 * @property int $coupon_id
 * @property string $coupon_name
 * @property string|null $coupon_description
 * @property string $coupon_type F=fixed, P=percentage
 * @property string $coupon_code
 * @property float $coupon_amount
 * @property float $coupon_minimum_order
 * @property string|null $coupon_start_date
 * @property string|null $coupon_expire_date
 * @property int $uses_per_coupon
 * @property int $uses_per_user
 * @property string $coupon_active
 * @property string|null $date_created
 */
class Coupons extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const COUPON_TYPE_F = 'F';
    const COUPON_TYPE_P = 'P';
    const COUPON_ACTIVE_Y = 'Y';
    const COUPON_ACTIVE_N = 'N';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'coupons';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['coupon_description', 'coupon_start_date', 'coupon_expire_date', 'date_created'], 'default', 'value' => null],
            [['coupon_type'], 'default', 'value' => 'F'],
            [['coupon_minimum_order'], 'default', 'value' => 0.00],
            [['uses_per_coupon'], 'default', 'value' => 1],
            [['uses_per_user'], 'default', 'value' => 0],
            [['coupon_active'], 'default', 'value' => 'Y'],
            [['coupon_name', 'coupon_code'], 'required'],
            [['coupon_description', 'coupon_type', 'coupon_active'], 'string'],
            [['coupon_amount', 'coupon_minimum_order'], 'number'],
            [['coupon_start_date', 'coupon_expire_date', 'date_created'], 'safe'],
            [['uses_per_coupon', 'uses_per_user'], 'integer'],
            [['coupon_name', 'coupon_code'], 'string', 'max' => 32],
            ['coupon_type', 'in', 'range' => array_keys(self::optsCouponType())],
            ['coupon_active', 'in', 'range' => array_keys(self::optsCouponActive())],
            [['coupon_code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'coupon_id' => 'Coupon ID',
            'coupon_name' => 'Coupon Name',
            'coupon_description' => 'Coupon Description',
            'coupon_type' => 'Coupon Type',
            'coupon_code' => 'Coupon Code',
            'coupon_amount' => 'Coupon Amount',
            'coupon_minimum_order' => 'Coupon Minimum Order',
            'coupon_start_date' => 'Coupon Start Date',
            'coupon_expire_date' => 'Coupon Expire Date',
            'uses_per_coupon' => 'Uses Per Coupon',
            'uses_per_user' => 'Uses Per User',
            'coupon_active' => 'Coupon Active',
            'date_created' => 'Date Created',
        ];
    }


    /**
     * column coupon_type ENUM value labels
     * @return string[]
     */
    public static function optsCouponType()
    {
        return [
            self::COUPON_TYPE_F => 'F',
            self::COUPON_TYPE_P => 'P',
        ];
    }

    /**
     * column coupon_active ENUM value labels
     * @return string[]
     */
    public static function optsCouponActive()
    {
        return [
            self::COUPON_ACTIVE_Y => 'Y',
            self::COUPON_ACTIVE_N => 'N',
        ];
    }

    /**
     * @return string
     */
    public function displayCouponType()
    {
        return self::optsCouponType()[$this->coupon_type];
    }

    /**
     * @return bool
     */
    public function isCouponTypeF()
    {
        return $this->coupon_type === self::COUPON_TYPE_F;
    }

    public function setCouponTypeToF()
    {
        $this->coupon_type = self::COUPON_TYPE_F;
    }

    /**
     * @return bool
     */
    public function isCouponTypeP()
    {
        return $this->coupon_type === self::COUPON_TYPE_P;
    }

    public function setCouponTypeToP()
    {
        $this->coupon_type = self::COUPON_TYPE_P;
    }

    /**
     * @return string
     */
    public function displayCouponActive()
    {
        return self::optsCouponActive()[$this->coupon_active];
    }

    /**
     * @return bool
     */
    public function isCouponActiveY()
    {
        return $this->coupon_active === self::COUPON_ACTIVE_Y;
    }

    public function setCouponActiveToY()
    {
        $this->coupon_active = self::COUPON_ACTIVE_Y;
    }

    /**
     * @return bool
     */
    public function isCouponActiveN()
    {
        return $this->coupon_active === self::COUPON_ACTIVE_N;
    }

    public function setCouponActiveToN()
    {
        $this->coupon_active = self::COUPON_ACTIVE_N;
    }
}
