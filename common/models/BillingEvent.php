<?php

declare(strict_types=1);

namespace common\models;

use common\models\traits\AutoTimestampTrait;

class BillingEvent extends base\BillingEvent
{
    use AutoTimestampTrait;

    /**
     * Cancelled events release their period (DB index uses live_slot), so the
     * generated unique rule must ignore them too or rebilling fails validation.
     */
    #[\Override]
    public function rules()
    {
        $rules = array_filter(
            parent::rules(),
            static fn(array $rule): bool => ($rule[1] ?? null) !== 'unique'
        );
        $rules[] = [
            ['item_type', 'item_id', 'period_start', 'period_end'],
            'unique',
            'targetAttribute' => ['item_type', 'item_id', 'period_start', 'period_end'],
            'filter' => ['<>', 'status', self::STATUS_CANCELLED],
            'when' => static fn(self $model): bool => $model->status !== self::STATUS_CANCELLED,
        ];
        return array_values($rules);
    }

    public static function getStatuses(): array
    {
        return parent::optsStatus();
    }

    public static function previousFulfilled(
        int $itemType,
        int $itemId
    ): ?self {
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
    ): int {
        return (int) static::find()
                        ->where([
                            'item_type' => $itemType,
                            'item_id' => $itemId,
                            'status' => self::STATUS_FULFILLED,
                        ])
                        ->count();
    }
}
