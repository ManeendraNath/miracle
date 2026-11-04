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
 * @property-read string|null $websiteHref
 */
class Maintenance extends base\Maintenance
{
    use AutoTimestampTrait;

    public const STATUS_CANCELLED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_OVERDUE = 2;

    public static function getStatuses(): array
    {
        return [
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_OVERDUE => 'Overdue',
        ];
    }

    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['start_date', 'current_expiry_date'], 'date', 'format' => 'php:Y-m-d'],
            // Rendered as a link for staff: only http(s), never javascript: or app-relative paths.
            ['website_url', 'url', 'defaultScheme' => 'https'],
            ['renewal_cycle_months', 'integer', 'min' => 1],
            ['renewal_increase_percent', 'number', 'min' => 0, 'max' => 100],
            ['renewal_base_amount', 'number', 'min' => 0],
            ['next_renewal_amount', 'number', 'min' => 0.01],
            ['next_renewal_amount', 'default', 'value' => null],
            ['status', 'in', 'range' => [self::STATUS_CANCELLED, self::STATUS_ACTIVE, self::STATUS_OVERDUE]],
        ]);
    }

    /** website_url as a link target, or null for legacy values that aren't http(s). */
    public function getWebsiteHref(): ?string
    {
        return preg_match('~^https?://~i', (string) $this->website_url) ? $this->website_url : null;
    }
}
