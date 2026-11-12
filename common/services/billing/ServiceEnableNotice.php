<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\BillingSettings;
use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Maintenance;
use common\models\User;
use DateTimeImmutable;
use Yii;

/**
 * The question staff see before making a service Active again (the Enable icon on the service lists):
 * no payment is recorded, and what happens about its renewal invoice. Mirrors the billing collectors' rules.
 */
final class ServiceEnableNotice
{
    public static function confirm(Domains|Hosting|Maintenance $service, ?DateTimeImmutable $today = null): string
    {
        [$type, $noun] = match (true) {
            $service instanceof Domains => [InvoiceItem::TYPE_DOMAIN, 'domain'],
            $service instanceof Hosting => [InvoiceItem::TYPE_HOSTING, 'hosting account'],
            default => [InvoiceItem::TYPE_MAINTENANCE, 'maintenance contract'],
        };
        $status = $service::getStatuses()[(int) $service->status] ?? 'not Active';

        return 'This ' . $noun . ' is ' . $status . '. Make it Active? No payment is recorded.' . "\n\n" . self::invoiceNote($service, $type, $today ?? new DateTimeImmutable('today'));
    }

    /** @var array<int, BillingSettings> per request: the list pages ask once per disabled row */
    private static array $settings = [];

    private static function invoiceNote(Domains|Hosting|Maintenance $service, int $type, DateTimeImmutable $today): string
    {
        // Same invoices RenewalReleaseService looks at: Fulfilled too (a payment refunded as "still owed"),
        // and a carried-forward one is owed on the newer invoice carrying it.
        $where = ['item_type' => $type, 'item_id' => (int) $service->id];
        $invoiceIds = BillingEvent::find()->select('invoice_id')->distinct()
            ->where($where + ['status' => [BillingEvent::STATUS_INVOICED, BillingEvent::STATUS_FULFILLED]])->column();
        foreach (Invoice::find()->where(['id' => $invoiceIds, 'status' => [Invoice::STATUS_DRAFT, Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_CARRIED_FORWARD]])->all() as $invoice) {
            $open = $invoice->status === Invoice::STATUS_CARRIED_FORWARD ? (new InvoicePaymentService())->openInvoiceCarrying((int) $invoice->id) : $invoice;
            if ($open !== null) {
                return 'Its renewal is already owed on invoice ' . $open->reference() . ' (' . $open->statusLabel() . '), so no new invoice is made.';
            }
        }
        if (User::find()->where(['id' => (int) $service->user_id, 'status' => User::getStatusDeleted()])->exists()) {
            return 'Its customer is deleted, so no renewal invoice is made.';
        }
        // The billing run skips a period that already has a renewal (BillingEventService::findExisting / findInvoicedOverlap);
        // a Pending one left by a failed run is invoiced by the next run.
        $nextPeriod = (new DateTimeImmutable((string) $service->current_expiry_date))->modify('+1 day')->format('Y-m-d');
        $billed = BillingEvent::find()->where($where)->andWhere(['or',
            ['and', ['period_start' => $nextPeriod], ['not in', 'status', [BillingEvent::STATUS_CANCELLED, BillingEvent::STATUS_PENDING]]],
            ['status' => BillingEvent::STATUS_INVOICED],
        ])->exists();
        if ($billed) {
            return 'Its renewal for this period is already billed, so no new invoice is made.';
        }
        $settings = self::$settings[$type] ??= BillingSettings::forType($type);
        if (!(bool) $settings->is_active) {
            return 'Automatic billing for this kind of service is turned off, so no renewal invoice is made.';
        }
        if ((float) $service->renewal_base_amount <= 0 && (float) $service->next_renewal_amount <= 0) {
            return 'It has no renewal price, so no renewal invoice is made until one is set.';
        }
        // The run also needs these (same checks as InvoicePaymentService::renewalState).
        if (!\common\models\CustomerBillingProfile::find()->where(['user_id' => (int) $service->user_id])->exists()) {
            return 'The customer has no billing profile, so no renewal invoice can be made until one is added.';
        }
        if (!\common\models\CronJob::isEnabled('cron/run-billing')) {
            return 'The daily billing job is switched off (Cron Jobs & Logs), so no renewal invoice is made until it is on.';
        }
        $days = max(0, (int) $settings->invoice_before_days);
        $expiry = new DateTimeImmutable((string) $service->current_expiry_date);
        $formatter = Yii::$app->formatter;
        if ($expiry <= $today->modify('+' . $days . ' days')) {
            return 'Its renewal (expiry ' . $formatter->asDate($expiry, 'php:d M Y') . ') will be invoiced by the next daily billing run.';
        }

        return 'Its renewal will be invoiced by the daily billing run from ' . $formatter->asDate($expiry->modify('-' . $days . ' days'), 'php:d M Y') . ', ' . $days . ' days before it expires on ' . $formatter->asDate($expiry, 'php:d M Y') . '.';
    }
}
