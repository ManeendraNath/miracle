<?php

namespace common\models;

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
class Coupons extends base\Coupons
{

}
