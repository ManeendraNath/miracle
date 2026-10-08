<?php

namespace common\models;

/**
 * This is the model class for table "maintenance".
 *
 * @property int $id
 * @property int $user_id
 * @property string $website_url
 * @property string $start_date
 * @property string $current_expiry_date Extended automatically when a renewal history ledger record is verified
 * @property int $status 0: Cancelled, 1: Active, 2: Overdue
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */
class Maintenance extends base\Maintenance
{

}
