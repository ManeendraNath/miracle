<?php

namespace common\models;


/**
 * This is the model class for table "customer_notification".
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property string|null $route
 * @property int $is_read
 * @property int|null $read_at
 * @property int $created_at
 *
 * @property User $user
 */
class CustomerNotification extends base\CustomerNotification
{

}
