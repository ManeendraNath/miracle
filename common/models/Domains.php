<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

/**
 * Domain model.
 *
 * @property int $id
 * @property int $user_id
 * @property string $domain_url
 * @property string $registrar
 * @property string $registered_date
 * @property string $current_expiry_date
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property User $user
 */

class Domains extends base\Domains
{
    use AutoTimestampTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_DELETED = 2;

    /**
     * Available Domain statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DELETED => 'Deleted',
        ];
    }

    /**
     * Additional business validation rules.
     */
    #[\Override]
    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                            [['registered_date', 'current_expiry_date'], 'required'],
                            [['registered_date', 'current_expiry_date'], 'date', 'format' => 'php:Y-m-d'],
                            ['renewal_cycle_months', 'integer', 'min' => 1],
                            ['renewal_increase_percent', 'number', 'min' => 0, 'max' => 100],
                            ['renewal_base_amount', 'number', 'min' => 0],
                            ['next_renewal_amount', 'number', 'min' => 0.01],
                            ['next_renewal_amount', 'default', 'value' => null],
                            ['status', 'in', 'range' => array_keys(self::getStatuses())],
                        ]
        );
    }

    /**
     * Custom labels used by forms, grids and detail views.
     */
    #[\Override]
    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
            parent::attributeLabels(),
            [
                            'user_id' => 'Client',
                            'domain_url' => 'Domain Name',
                            'registered_date' => 'Registered',
                            'current_expiry_date' => 'Expiry Date',
                            'renewal_cycle_months' => 'Renewal Cycle (Months)',
                            'renewal_increase_percent' => 'Renewal Increase (%)',
                        ]
        );
    }
}
