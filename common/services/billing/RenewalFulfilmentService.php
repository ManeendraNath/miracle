<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Maintenance;
use common\models\ServiceRenewalHistory;
use common\models\Transaction;
use Yii;

final class RenewalFulfilmentService
{

    public function fulfilInvoice(
            Invoice $invoice,
            bool $useTransaction = true
    ): void
    {
        if ($invoice->status !== Invoice::STATUS_PAID) {
            return;
        }

        $transactionId = Transaction::find()
                ->select('id')
                ->where([
                    'invoice_id' => $invoice->id,
                    'status' => Transaction::STATUS_CAPTURED,
                ])
                ->orderBy(['paid_at' => SORT_DESC, 'id' => SORT_DESC])
                ->scalar();

        if ($useTransaction) {
            $dbTransaction = Yii::$app->db->beginTransaction();
        } else {
            $dbTransaction = null;
        }

        try {
            $events = BillingEvent::find()
                    ->where([
                        'invoice_id' => $invoice->id,
                        'status' => BillingEvent::STATUS_INVOICED,
                    ])
                    ->orderBy(['id' => SORT_ASC])
                    ->all();

            foreach ($events as $event) {
                $this->fulfilEvent(
                        $event,
                        $transactionId ? (int) $transactionId : null
                );
            }

            if (
                    $dbTransaction !== null && $dbTransaction->isActive
            ) {
                $dbTransaction->commit();
            }
        } catch (\Throwable $e) {
            if (
                    $dbTransaction !== null && $dbTransaction->isActive
            ) {
                $dbTransaction->rollBack();
            }

            Yii::error(
                    $e,
                    __METHOD__
            );

            throw $e;
        }
    }

    private function fulfilEvent(
            BillingEvent $event,
            ?int $transactionId
    ): void
    {
        $exists = ServiceRenewalHistory::find()
                ->where([
                    'billing_event_id' => $event->id,
                ])
                ->exists();

        if ($exists) {
            return;
        }

        $asset = $this->findAsset($event);

        if ($asset === null) {
            throw new \RuntimeException(
                            "Billing asset not found for event {$event->id}."
                    );
        }

        $months = $this->monthDifference(
                $event->period_start,
                $event->period_end
        );

        $history = new ServiceRenewalHistory();

        $history->billing_event_id = $event->id;
        $history->item_type = $this->historyType(
                (int) $event->item_type
        );

        $history->item_id = $event->item_id;
        $history->invoice_id = $event->invoice_id;
        $history->transaction_id = $transactionId;

        $history->billed_amount = $event->amount;
        $history->renewal_period_months = $months;

        $history->start_date = $event->period_start;
        $history->end_date = $event->period_end;

        $history->logged_at = time();

        if (!$history->save()) {
            throw new \RuntimeException(
                            json_encode($history->errors)
                    );
        }

        $now = time();

        $asset->current_expiry_date = $event->period_end;

        $asset->updated_at = $now;

        if (!$asset->save(
                        false,
                        [
                            'current_expiry_date',
                            'updated_at',
                        ]
                )) {
            throw new \RuntimeException(
                            'Unable to update service expiry.'
                    );
        }

        $event->status = BillingEvent::STATUS_FULFILLED;

        $event->updated_at = $now;

        if (!$event->save(
                        false,
                        [
                            'status',
                            'updated_at',
                        ]
                )) {
            throw new \RuntimeException(
                            'Unable to fulfil billing event.'
                    );
        }
    }

    private function findAsset(
            BillingEvent $event
    ): object|null
    {
        return match ((int) $event->item_type) {
            InvoiceItem::TYPE_DOMAIN =>
            Domains::findOne($event->item_id),
            InvoiceItem::TYPE_HOSTING =>
            Hosting::findOne($event->item_id),
            InvoiceItem::TYPE_MAINTENANCE =>
            Maintenance::findOne($event->item_id),
            default => null,
        };
    }

    private function historyType(
            int $type
    ): string
    {
        return match ($type) {
            InvoiceItem::TYPE_DOMAIN => 'domain',
            InvoiceItem::TYPE_HOSTING => 'hosting',
            InvoiceItem::TYPE_MAINTENANCE => 'maintenance',
            default => throw new \RuntimeException(
                    'Unsupported renewable service.'
            ),
        };
    }

    private function monthDifference(
            string $start,
            string $end
    ): int
    {
        $a = new \DateTimeImmutable($start);
        $b = new \DateTimeImmutable($end);

        $b = $b->modify('+1 day');

        $diff = $a->diff($b);

        return ($diff->y * 12) + $diff->m;
    }
}
