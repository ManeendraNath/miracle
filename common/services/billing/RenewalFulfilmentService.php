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
use common\services\admin\AdminAlertService;
use Yii;

final class RenewalFulfilmentService
{
    public function fulfilInvoice(Invoice $invoice, bool $useTransaction = true): void
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
                    'invoice_id' => self::coveredInvoiceIds((int) $invoice->id),
                    'status' => BillingEvent::STATUS_INVOICED,
                ])
                ->orderBy(['id' => SORT_ASC])
                ->all();

            foreach ($events as $event) {
                $this->fulfilEvent($event, (int) $invoice->id, $transactionId ? (int) $transactionId : null);
            }

            if ($dbTransaction !== null && $dbTransaction->isActive) {
                $dbTransaction->commit();
            }
        } catch (\Throwable $e) {
            if ($dbTransaction !== null && $dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }

            Yii::error($e, __METHOD__);

            throw $e;
        }
    }

    /**
     * Fulfilled renewals of a service whose paying invoice was closed as refunded with no money kept for them: they
     * no longer count as paid periods or towards the price. Decided per renewal: money kept on its own invoice, or on
     * any invoice its balance was carried to, paid for it. Money kept on an older invoice carried in did not.
     *
     * @return int[]
     */
    public function refundedEventIds(int $itemType, int $itemId): array
    {
        $rows = ServiceRenewalHistory::find()->alias('h')
            ->innerJoin(['i' => Invoice::tableName()], 'i.id = h.invoice_id')
            ->innerJoin(['e' => BillingEvent::tableName()], 'e.id = h.billing_event_id')
            ->select(['h.billing_event_id', 'e.invoice_id', 'paid' => 'h.invoice_id'])
            ->where(['h.item_type' => $this->historyType($itemType), 'h.item_id' => $itemId, 'i.status' => Invoice::STATUS_REFUNDED])
            ->asArray()->all();
        $refunded = [];
        foreach ($rows as $row) {
            if (!$this->moneyKeptOnTheWay((int) $row['invoice_id'], (int) $row['paid'])) {
                $refunded[] = (int) $row['billing_event_id'];
            }
        }

        return $refunded;
    }

    /** From the renewal's invoice forward along arrears lines to the invoice it was finally paid on. */
    private function moneyKeptOnTheWay(int $fromInvoiceId, int $paidInvoiceId): bool
    {
        // Each step goes to a newer invoice, so this can't loop.
        for ($id = $fromInvoiceId; $id !== 0;) {
            if (self::moneyKept([$id])) {
                return true;
            }
            if ($id === $paidInvoiceId) {
                return false;
            }
            $id = (int) InvoiceItem::find()->select('invoice_id')->where(['source_invoice_id' => $id, 'item_type' => InvoiceItem::TYPE_ARREARS])->orderBy(['id' => SORT_DESC])->scalar();
        }

        return false;
    }

    /** @param int[] $invoiceIds */
    private static function moneyKept(array $invoiceIds): bool
    {
        return Transaction::find()->where(['invoice_id' => $invoiceIds, 'status' => Transaction::STATUS_CAPTURED, 'is_unapplied' => 0])->exists();
    }

    /**
     * Ids of invoices closed as refunded with no money kept anywhere on them or on what was carried into them.
     *
     * ponytail: walks each refunded invoice's chain in PHP; refunded invoices are few. Cache it if that changes.
     *
     * @return int[]
     */
    public static function fullyRefundedInvoices(): array
    {
        $ids = [];
        foreach (Invoice::find()->select('id')->where(['status' => Invoice::STATUS_REFUNDED])->column() as $id) {
            if (!self::moneyKept(self::coveredInvoiceIds((int) $id))) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * The paid invoice plus every invoice whose balance was carried
     * forward into it (recursively), since their BillingEvents keep
     * pointing at the original source invoice.
     *
     * @return int[]
     */
    private static function coveredInvoiceIds(int $invoiceId): array
    {
        $ids = [$invoiceId];
        $pending = [$invoiceId];

        while ($pending) {
            $sourceIds = InvoiceItem::find()
                ->select('source_invoice_id')
                ->where([
                    'invoice_id' => $pending,
                    'item_type' => InvoiceItem::TYPE_ARREARS,
                ])
                ->andWhere(['not', ['source_invoice_id' => null]])
                ->column();

            $pending = array_diff(array_map('intval', $sourceIds), $ids);
            $ids = array_merge($ids, $pending);
        }

        return $ids;
    }

    private function fulfilEvent(BillingEvent $event, int $paidInvoiceId, ?int $transactionId): void
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
            throw new \RuntimeException("Billing asset not found for event {$event->id}.");
        }

        $months = RenewalPeriodCalculator::months((string) $event->period_start, (string) $event->period_end);

        $history = new ServiceRenewalHistory();

        $history->billing_event_id = $event->id;
        $history->item_type = $this->historyType((int) $event->item_type);

        $history->item_id = $event->item_id;
        // The invoice that was paid (for a carried-forward event, the newer one), so it
        // pairs with transaction_id; the original invoice stays on the billing event.
        $history->invoice_id = $paidInvoiceId;
        $history->transaction_id = $transactionId;

        $history->billed_amount = $event->amount;
        $history->renewal_period_months = $months;

        $history->start_date = $event->period_start;
        $history->end_date = $event->period_end;

        $history->logged_at = time();

        if (!$history->save()) {
            throw new \RuntimeException(json_encode($history->errors));
        }

        $now = time();

        $asset->updated_at = $now;
        $attributes = ['current_expiry_date', 'updated_at'];
        // Never shorten: an admin may have extended the asset by hand meanwhile (and then the anchor stays theirs).
        if ((string) $asset->current_expiry_date < (string) $event->period_end) {
            $asset->current_expiry_date = $event->period_end;
            // Staff moved the expiry since the anchor was set: later periods must follow the date just granted.
            // Not a whole-year period ending in February: 28 or 29 there says nothing about the anchor.
            $endsOn = strtotime((string) $event->period_end);
            $februaryYear = $months % 12 === 0 && $endsOn !== false && (int) date('n', $endsOn) === 2;
            if ($asset->renewal_anchor_day !== null && $endsOn !== false && !$februaryYear && (int) date('j', $endsOn) !== min((int) $asset->renewal_anchor_day, (int) date('t', $endsOn))) {
                $asset->renewal_anchor_day = RenewalPeriodCalculator::anchorDayFor((string) $event->period_end);
                $attributes[] = 'renewal_anchor_day';
            }
        }
        // The one-time price was used for this renewal; later ones are calculated from it again. A different
        // value was set meanwhile for the renewal after this one, so it stays.
        if ((int) $event->is_manual_amount && $asset->next_renewal_amount !== null
            && abs((float) $asset->next_renewal_amount - (float) $event->amount) < 0.005) {
            $asset->next_renewal_amount = null;
            $attributes[] = 'next_renewal_amount';
        }
        // Paid up again: a service staff suspended for non-payment goes back into billing and reminders.
        // Inactive / cancelled services are disabled on purpose and stay as they are.
        if (($asset instanceof Hosting && (int) $asset->status === Hosting::STATUS_SUSPENDED)
            || ($asset instanceof Maintenance && (int) $asset->status === Maintenance::STATUS_OVERDUE)) {
            $asset->status = $asset instanceof Hosting ? Hosting::STATUS_ACTIVE : Maintenance::STATUS_ACTIVE;
            $attributes[] = 'status';
            AdminAlertService::serviceReactivatedByPayment($asset instanceof Hosting ? (string) $asset->primary_domain : (string) $asset->website_url, (string) Invoice::find()->select('invoice_number')->where(['id' => $paidInvoiceId])->scalar());
        }

        if (!$asset->save(false, $attributes)) {
            throw new \RuntimeException('Unable to update service expiry.');
        }

        $event->status = BillingEvent::STATUS_FULFILLED;

        $event->updated_at = $now;

        if (!$event->save(false, ['status', 'updated_at'])) {
            throw new \RuntimeException('Unable to fulfil billing event.');
        }
    }

    private function findAsset(BillingEvent $event): object|null
    {
        return match ((int) $event->item_type) {
            InvoiceItem::TYPE_DOMAIN => Domains::findOne($event->item_id),
            InvoiceItem::TYPE_HOSTING => Hosting::findOne($event->item_id),
            InvoiceItem::TYPE_MAINTENANCE => Maintenance::findOne($event->item_id),
            default => null,
        };
    }

    private function historyType(int $type): string
    {
        return match ($type) {
            InvoiceItem::TYPE_DOMAIN => 'domain',
            InvoiceItem::TYPE_HOSTING => 'hosting',
            InvoiceItem::TYPE_MAINTENANCE => 'maintenance',
            default => throw new \RuntimeException('Unsupported renewable service.'),
        };
    }

}
