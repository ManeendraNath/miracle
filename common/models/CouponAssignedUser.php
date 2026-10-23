<?php

namespace common\models;


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
class CouponAssignedUser extends base\CouponAssignedUser
{

}
