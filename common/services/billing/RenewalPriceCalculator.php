<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\InvoiceItem;
use common\services\billing\dto\RenewalPriceResult;

/**
 * Renewal price = the previous final billed amount, raised by the increase once per year:
 * domains/hosting at every yearly renewal (every 12th monthly one), maintenance at the start of every
 * 3-year block. When the cycle length changed since the previous renewal (e.g. yearly -> monthly),
 * that amount no longer fits, so the service's renewal base amount for the new cycle is used instead.
 */
final class RenewalPriceCalculator
{
    public function calculate(
        int $itemType,
        int $itemId,
        float $baseAmount,
        float $increasePercent,
        int $cycleMonths = 12
    ): RenewalPriceResult {
        if ($baseAmount < 0) {
            throw new \InvalidArgumentException('Base amount cannot be negative.');
        }
        if ($increasePercent < 0) {
            throw new \InvalidArgumentException('Increase percentage cannot be negative.');
        }

        $previous = BillingEvent::previousFulfilled($itemType, $itemId);
        if ($previous === null || RenewalPeriodCalculator::months((string) $previous->period_start, (string) $previous->period_end) !== $cycleMonths) {
            return new RenewalPriceResult(round($baseAmount, 2), $previous === null ? round($increasePercent, 2) : 0.0, round($baseAmount, 2));
        }

        $renewalsPerYear = max(1, intdiv(12, $cycleMonths));
        $block = $itemType === InvoiceItem::TYPE_MAINTENANCE ? 3 * $renewalsPerYear : $renewalsPerYear;
        $completed = BillingEvent::fulfilledCount($itemType, $itemId);
        $previousAmount = (float) $previous->amount;
        $amount = $completed % $block === 0 ? $previousAmount * (1 + ($increasePercent / 100)) : $previousAmount;

        return new RenewalPriceResult(round($previousAmount, 2), round($increasePercent, 2), round($amount, 2));
    }
}
