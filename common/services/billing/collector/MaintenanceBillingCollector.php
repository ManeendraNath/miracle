<?php

declare(strict_types=1);

namespace common\services\billing\collector;

use common\models\BillingSettings;
use common\models\InvoiceItem;
use common\models\Maintenance;
use common\models\User;
use common\services\billing\dto\BillableRenewal;
use DateTimeImmutable;

final class MaintenanceBillingCollector
{
    /** A monthly plan is invoiced at most this many days ahead: the global lead time could be a whole month. */
    public const MONTHLY_LEAD_DAYS = 7;

    /** Days before expiry a service of this type and cycle is invoiced (renewal pages and staff notes say the same). */
    public static function leadDays(int $itemType, ?int $cycleMonths, int $globalDays): int
    {
        $globalDays = max(0, $globalDays);

        return $itemType === InvoiceItem::TYPE_MAINTENANCE && $cycleMonths === 1 ? min($globalDays, self::MONTHLY_LEAD_DAYS) : $globalDays;
    }

    /**
     * Return Maintenance renewals currently inside their
     * configured automatic-invoice window.
     *
     * @return BillableRenewal[]
     */
    public function collect(
        DateTimeImmutable $today
    ): array {
        $settings = BillingSettings::forType(
            InvoiceItem::TYPE_MAINTENANCE
        );

        if (!(bool) $settings->is_active) {
            return [];
        }

        $invoiceBeforeDays = max(
            0,
            (int) $settings->invoice_before_days
        );

        $cutoff = $today->modify(
            '+' . $invoiceBeforeDays . ' days'
        );

        $models = Maintenance::find()
                ->with('productOrder.product') // care plans and software subscriptions renew under their own name
                ->where([
                    'status' => 1,
                ])
                ->andWhere(['not in', 'user_id', User::deletedIds()])
                // A one-time Next Renewal Price is a price too.
                ->andWhere(['or', ['>', 'renewal_base_amount', 0], ['>', 'next_renewal_amount', 0]])
                ->andWhere([
                    '<=',
                    'current_expiry_date',
                    $cutoff->format('Y-m-d'),
                ])
                ->orderBy([
                    'current_expiry_date' => SORT_ASC,
                    'id' => SORT_ASC,
                ])
                ->all();

        $renewals = [];

        $monthlyCutoff = $today->modify('+' . self::leadDays(InvoiceItem::TYPE_MAINTENANCE, 1, $invoiceBeforeDays) . ' days')->format('Y-m-d');
        foreach ($models as $model) {
            if ((int) $model->renewal_cycle_months === 1 && (string) $model->current_expiry_date > $monthlyCutoff) {
                continue;
            }
            $renewals[] = new BillableRenewal(
                userId: (int) $model->user_id,
                itemType:
                    InvoiceItem::TYPE_MAINTENANCE,
                itemId: (int) $model->id,
                description:
                    (($model->storeProduct()?->name ?? 'Maintenance') . ' renewal - ')
                    . $model->website_url,
                currentExpiryDate:
                    (string) $model->current_expiry_date,
                baseAmount:
                    (float) $model->renewal_base_amount,
                cycleMonths:
                    $model->renewal_cycle_months !== null ? (int) $model->renewal_cycle_months : null,
                increasePercent:
                    $model->renewal_increase_percent !== null ? (float) $model->renewal_increase_percent : null,
                anchorDay:
                    $model->renewal_anchor_day !== null ? (int) $model->renewal_anchor_day : null,
                overrideAmount:
                    $model->next_renewal_amount !== null ? (float) $model->next_renewal_amount : null
            );
        }

        return $renewals;
    }
}
