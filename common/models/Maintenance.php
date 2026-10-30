<?php

namespace common\models;

use common\models\traits\AutoTimestampTrait;

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
    use AutoTimestampTrait;

    public const STATUS_CANCELLED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_OVERDUE = 2;

    #[\Override]
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['start_date', 'current_expiry_date'], 'date', 'format' => 'php:Y-m-d'],
            ['renewal_cycle_months', 'integer', 'min' => 1],
            ['renewal_increase_percent', 'number', 'min' => 0, 'max' => 100],
            ['renewal_base_amount', 'number', 'min' => 0],
            ['status', 'in', 'range' => [self::STATUS_CANCELLED, self::STATUS_ACTIVE, self::STATUS_OVERDUE]],
        ]);
    }
}
