<?php

declare(strict_types=1);

namespace common\services\billing\collector;

use common\models\BillingSettings;
use common\models\Hosting;
use common\models\InvoiceItem;
use common\services\billing\dto\BillableRenewal;
use DateTimeImmutable;

final class HostingBillingCollector
{

    /**
     * Return Hosting renewals currently inside their
     * configured automatic-invoice window.
     *
     * @return BillableRenewal[]
     */
    public function collect(
            DateTimeImmutable $today
    ): array
    {
        $settings = BillingSettings::forType(
                InvoiceItem::TYPE_HOSTING
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

        $models = Hosting::find()
                ->where([
                    'status' => Hosting::STATUS_ACTIVE,
                ])
                ->andWhere([
                    'not',
                    ['renewal_base_amount' => null],
                ])
                ->andWhere([
                    '>',
                    'renewal_base_amount',
                    0,
                ])
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

        foreach ($models as $model) {
            $renewals[] = new BillableRenewal(
                    userId: (int) $model->user_id,
                    itemType: InvoiceItem::TYPE_HOSTING,
                    itemId: (int) $model->id,
                    description:
                    'Hosting renewal - '
                    . $model->primary_domain,
                    currentExpiryDate:
                    (string) $model->current_expiry_date,
                    baseAmount:
                    (float) $model->renewal_base_amount,
                    cycleMonths:
                    $model->renewal_cycle_months !== null ? (int) $model->renewal_cycle_months : null,
                    increasePercent:
                    $model->renewal_increase_percent !== null ? (float) $model->renewal_increase_percent : null
            );
        }

        return $renewals;
    }
}
