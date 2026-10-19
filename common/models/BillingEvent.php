<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;

class BillingEvent extends base\BillingEvent
{

    use AutoTimestampTrait;

    public static function getStatuses(): array
    {
        return parent::optsStatus();
    }

    public static function previousFulfilled(
            int $itemType,
            int $itemId
    ): ?self
    {
        return static::find()
                        ->where([
                            'item_type' => $itemType,
                            'item_id' => $itemId,
                            'status' => self::STATUS_FULFILLED,
                        ])
                        ->orderBy([
                            'period_end' => SORT_DESC,
                            'id' => SORT_DESC,
                        ])
                        ->one();
    }

    public static function fulfilledCount(
            int $itemType,
            int $itemId
    ): int
    {
        return (int) static::find()
                        ->where([
                            'item_type' => $itemType,
                            'item_id' => $itemId,
                            'status' => self::STATUS_FULFILLED,
                        ])
                        ->count();
    }
}
