<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\InvoiceItem;
use common\services\billing\dto\RenewalPriceResult;

final class RenewalPriceCalculator
{

    public function calculate(
            int $itemType,
            int $itemId,
            float $baseAmount,
            float $increasePercent
    ): RenewalPriceResult
    {
        if ($baseAmount < 0) {
            throw new \InvalidArgumentException(
                            'Base amount cannot be negative.'
                    );
        }

        if ($increasePercent < 0) {
            throw new \InvalidArgumentException(
                            'Increase percentage cannot be negative.'
                    );
        }

        if ($itemType === InvoiceItem::TYPE_MAINTENANCE) {
            return $this->calculateMaintenance(
                            $itemId,
                            $baseAmount,
                            $increasePercent
                    );
        }

        $previous = BillingEvent::previousFulfilled(
                $itemType,
                $itemId
        );

        if ($previous === null) {
            return new RenewalPriceResult(
                    round($baseAmount, 2),
                    round($increasePercent, 2),
                    round($baseAmount, 2)
            );
        }

        $previousAmount = (float) $previous->amount;

        $amount = $previousAmount * (1 + ($increasePercent / 100));

        return new RenewalPriceResult(
                round($previousAmount, 2),
                round($increasePercent, 2),
                round($amount, 2)
        );
    }

    private function calculateMaintenance(
            int $itemId,
            float $baseAmount,
            float $increasePercent
    ): RenewalPriceResult
    {
        $completed = BillingEvent::fulfilledCount(
                InvoiceItem::TYPE_MAINTENANCE,
                $itemId
        );

        $threeYearBlocks = intdiv($completed, 3);

        $multiplier = pow(
                1 + ($increasePercent / 100),
                $threeYearBlocks
        );

        $amount = round(
                $baseAmount * $multiplier,
                2
        );

        return new RenewalPriceResult(
                $amount,
                round($increasePercent, 2),
                $amount
        );
    }
}
