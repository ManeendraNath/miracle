<?php

namespace common\models;

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
class CouponApplicability extends base\CouponApplicability
{
    
}
