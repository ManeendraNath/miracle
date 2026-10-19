<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\BillingSettings;
use common\services\billing\dto\BillableRenewal;
use yii\db\IntegrityException;

final class BillingEventService
{

    public function __construct(
            private readonly RenewalPriceCalculator $prices = new RenewalPriceCalculator(),
            private readonly RenewalPeriodCalculator $periods = new RenewalPeriodCalculator()
    )
    {
        
    }

    public function create(
            int $runId,
            BillableRenewal $renewal
    ): BillingEvent
    {
        if ($runId <= 0) {
            throw new \InvalidArgumentException(
                            'A valid billing run ID is required.'
                    );
        }

        if ($renewal->userId <= 0) {
            throw new \InvalidArgumentException(
                            'A valid client ID is required.'
                    );
        }

        if ($renewal->itemId <= 0) {
            throw new \InvalidArgumentException(
                            'A valid renewable item ID is required.'
                    );
        }

        if ($renewal->baseAmount <= 0) {
            throw new \InvalidArgumentException(
                            'Renewal base amount must be greater than zero.'
                    );
        }

        $settings = BillingSettings::forType(
                $renewal->itemType
        );

        if (!(bool) $settings->is_active) {
            throw new \DomainException(
                            'Automatic billing is disabled for service type '
                            . $renewal->itemType
                            . '.'
                    );
        }

        $cycle = $renewal->cycleMonths ?? (int) $settings->renewal_cycle_months;

        $increase = $renewal->increasePercent ?? (float) $settings->renewal_increase_percent;

        if ($cycle <= 0) {
            throw new \DomainException(
                            'Renewal cycle must be greater than zero.'
                    );
        }

        if ($increase < 0) {
            throw new \DomainException(
                            'Renewal increase percentage cannot be negative.'
                    );
        }

        $period = $this->periods->calculate(
                $renewal->currentExpiryDate,
                $cycle
        );

        $existing = $this->findExisting(
    $renewal,
    $period['start'],
    $period['end']
);

if ($existing !== null) {
    if (
        $existing->status ===
        BillingEvent::STATUS_PENDING
        && $existing->invoice_id === null
        && (int) $existing->billing_run_id
            !== $runId
    ) {
        $existing->billing_run_id =
            $runId;

        $existing->updated_at =
            time();

        if (!$existing->save(
            false,
            [
                'billing_run_id',
                'updated_at',
            ]
        )) {
            throw new \RuntimeException(
                'Pending billing event could not '
                . 'be moved to the current billing run.'
            );
        }
    }

    return $existing;
}

        $price = $this->prices->calculate(
                $renewal->itemType,
                $renewal->itemId,
                $renewal->baseAmount,
                $increase
        );

        $paymentDueDays = max(
                0,
                (int) $settings->payment_due_days
        );

        $event = new BillingEvent();

        $event->billing_run_id = $runId;
        $event->user_id = $renewal->userId;
        $event->item_type = $renewal->itemType;
        $event->item_id = $renewal->itemId;

        $event->description = $renewal->description;

        $event->period_start = $period['start'];

        $event->period_end = $period['end'];

        $event->previous_amount = $price->previousAmount;

        $event->increase_percent = $price->increasePercent;

        $event->calculated_amount = $price->amount;

        $event->amount = $price->amount;

        $event->is_manual_amount = 0;

        $event->due_date = date(
                'Y-m-d',
                strtotime(
                        '+' . $paymentDueDays . ' days'
                )
        );

        $event->status = BillingEvent::STATUS_PENDING;

        try {
            if (!$event->save()) {
                throw new \RuntimeException(
                                'Unable to create billing event: '
                                . json_encode(
                                        $event->errors,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                )
                        );
            }

            return $event;
        } catch (IntegrityException $exception) {
            // Another process might have created the same
            // service-period event between lookup and INSERT.

            $existing = $this->findExisting(
                    $renewal,
                    $period['start'],
                    $period['end']
            );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function findExisting(
            BillableRenewal $renewal,
            string $periodStart,
            string $periodEnd
    ): ?BillingEvent
    {
        return BillingEvent::findOne([
                    'item_type' => $renewal->itemType,
                    'item_id' => $renewal->itemId,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
        ]);
    }
}
