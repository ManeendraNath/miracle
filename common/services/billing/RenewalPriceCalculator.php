<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\InvoiceItem;
use common\services\billing\dto\RenewalPriceResult;

/**
 * Renewal price = the previous final billed amount, raised by the increase each time the service passes a
 * year (maintenance: a 3-year block) of renewals on its current cycle: every yearly or longer renewal,
 * every 12th monthly one, the 2nd and 3rd of a 24-month maintenance (at 48 and 72 months), and so on.
 * When the cycle length changed since the previous renewal (e.g. yearly -> monthly), that amount no longer
 * fits, so the service's renewal base amount for the new cycle is used and the count starts again.
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

        $fulfilled = BillingEvent::find()
            ->where(['item_type' => $itemType, 'item_id' => $itemId, 'status' => BillingEvent::STATUS_FULFILLED])
            ->andWhere(['not in', 'id', (new RenewalFulfilmentService())->refundedEventIds($itemType, $itemId)])
            ->orderBy(['period_end' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
        // Renewals in a row on the current cycle, newest first; an older cycle doesn't count.
        $sameCycle = 0;
        foreach ($fulfilled as $event) {
            if (RenewalPeriodCalculator::months((string) $event->period_start, (string) $event->period_end) !== $cycleMonths) {
                break;
            }
            $sameCycle++;
        }
        if ($sameCycle === 0) {
            return new RenewalPriceResult(round($baseAmount, 2), 0.0, round($baseAmount, 2));
        }

        $span = $itemType === InvoiceItem::TYPE_MAINTENANCE ? 36 : 12;
        $previousAmount = (float) $fulfilled[0]->amount;
        $passesBoundary = intdiv($sameCycle * $cycleMonths, $span) > intdiv(($sameCycle - 1) * $cycleMonths, $span);
        $amount = $passesBoundary ? $previousAmount * (1 + ($increasePercent / 100)) : $previousAmount;

        // The percent actually applied: customers see it on the renewal.
        return new RenewalPriceResult(round($previousAmount, 2), $passesBoundary ? round($increasePercent, 2) : 0.0, round($amount, 2));
    }
}
