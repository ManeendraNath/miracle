<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;
use yii\helpers\ArrayHelper;

class Hosting extends base\Hosting
{
    use AutoTimestampTrait;

    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_SUSPENDED = 2;

    public static function getStatuses(): array
    {
        return [
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_SUSPENDED => 'Suspended / Expired',
        ];
    }

    public function rules(): array
    {
        return ArrayHelper::merge(
            parent::rules(),
            [
                [
                    ['renewal_cycle_months'],
                    'integer',
                    'min' => 1,
                ],

                [
                    ['renewal_increase_percent'],
                    'number',
                    'min' => 0,
                    'max' => 100,
                ],

                [
                    ['renewal_base_amount'],
                    'number',
                    'min' => 0,
                ],

                [
                    ['status'],
                    'in',
                    'range' => array_keys(
                        self::getStatuses()
                    ),
                ],
            ]
        );
    }

    public function attributeLabels(): array
    {
        return ArrayHelper::merge(
            parent::attributeLabels(),
            [
                'user_id' => 'Client',
                'renewal_cycle_months' => 'Renewal Cycle (Months)',
                'renewal_increase_percent' => 'Renewal Increase (%)',
                'renewal_base_amount' => 'Base Renewal Amount',
            ]
        );
    }
}