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
    ) {
    }

    public function create(
        int $runId,
        BillableRenewal $renewal
    ): BillingEvent {
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
            $period['start']
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

                // Due date counts from the run that actually invoices it, so a
                // delayed run doesn't produce an invoice that is already overdue.
                $existing->due_date = $this->dueDate($settings);

                $existing->updated_at =
                    time();

                if (
                    !$existing->save(
                        false,
                        [
                        'billing_run_id',
                        'due_date',
                        'updated_at',
                        ]
                    )
                ) {
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

        $event->due_date = $this->dueDate($settings);

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
                $period['start']
            );

            if ($existing !== null) {
                return $existing;
            }

            throw $exception;
        }
    }

    private function dueDate($settings): string
    {
        return date('Y-m-d', strtotime('+' . max(0, (int) $settings->payment_due_days) . ' days'));
    }

    private function findExisting(
        BillableRenewal $renewal,
        string $periodStart
    ): ?BillingEvent {
        // Cancelled events release their period so it can be billed again.
        // Matched on period_start only: events created before the month-end
        // fix in RenewalPeriodCalculator carry a different period_end.
        return BillingEvent::find()
                        ->where([
                            'item_type' => $renewal->itemType,
                            'item_id' => $renewal->itemId,
                            'period_start' => $periodStart,
                        ])
                        ->andWhere(['<>', 'status', BillingEvent::STATUS_CANCELLED])
                        ->one();
    }
}
