<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\Invoice;
use common\models\InvoiceItem;

/**
 * A service staff disabled is not renewed (refund policy): its renewal not invoiced yet is released, and an
 * unpaid renewal invoice holding only this service is cancelled. An invoice that also bills other services,
 * already has a payment, or now holds the renewal as a carried-forward balance is left for staff, as is every
 * invoice when the staff member may not cancel invoices (the returned warning names them).
 */
final class RenewalReleaseService
{
    public function releaseForDisabledService(int $itemType, int $itemId, bool $cancelInvoices = true): ?string
    {
        BillingEvent::updateAll(
            ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Service disabled by staff.', 'updated_at' => time()],
            ['item_type' => $itemType, 'item_id' => $itemId, 'status' => BillingEvent::STATUS_PENDING, 'invoice_id' => null]
        );

        $invoiceIds = BillingEvent::find()->select('invoice_id')->distinct()
            // Fulfilled too: a payment refunded as "still owed" leaves the invoice unpaid with its renewal granted.
            ->where(['item_type' => $itemType, 'item_id' => $itemId, 'status' => [BillingEvent::STATUS_INVOICED, BillingEvent::STATUS_FULFILLED]])
            ->column();
        $leftOpen = [];
        foreach (Invoice::find()->where(['id' => $invoiceIds, 'status' => [Invoice::STATUS_DRAFT, Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_CARRIED_FORWARD]])->all() as $invoice) {
            if ($invoice->status === Invoice::STATUS_CARRIED_FORWARD) {
                $newer = (new InvoicePaymentService())->openInvoiceCarrying((int) $invoice->id);
                if ($newer !== null) {
                    $leftOpen[] = $newer->invoice_number;
                }
                continue;
            }
            $otherLines = InvoiceItem::find()->where(['invoice_id' => $invoice->id])
                ->andWhere(['not', ['item_type' => $itemType, 'item_id' => $itemId]])->exists();
            if ($otherLines || !$cancelInvoices) {
                $leftOpen[] = $invoice->invoice_number;
                continue;
            }
            try {
                (new InvoiceCancellationService())->cancel($invoice, 'Service disabled by staff.');
            } catch (\DomainException) {
                $leftOpen[] = $invoice->invoice_number; // e.g. a payment already made: staff decide (refund)
            }
        }

        return $leftOpen === [] ? null : 'Renewal invoice ' . implode(', ', array_unique($leftOpen)) . ' still bills this service (it covers other items or a carried-forward balance, has a payment, or needs an Admin to cancel it). Cancel, refund or write off what the customer should not pay.';
    }
}
