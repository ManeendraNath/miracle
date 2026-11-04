<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\Invoice;
use common\models\InvoiceItem;

/**
 * A service staff disabled is not renewed (refund policy): its renewal not invoiced yet is released, and an
 * unpaid renewal invoice holding only this service is cancelled. An invoice that also bills other services,
 * or already has a payment, is left for staff (the returned warning names it).
 */
final class RenewalReleaseService
{
    public function releaseForDisabledService(int $itemType, int $itemId): ?string
    {
        BillingEvent::updateAll(
            ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Service disabled by staff.', 'updated_at' => time()],
            ['item_type' => $itemType, 'item_id' => $itemId, 'status' => BillingEvent::STATUS_PENDING, 'invoice_id' => null]
        );

        $invoiceIds = BillingEvent::find()->select('invoice_id')->distinct()
            ->where(['item_type' => $itemType, 'item_id' => $itemId, 'status' => BillingEvent::STATUS_INVOICED])
            ->column();
        $leftOpen = [];
        foreach (Invoice::find()->where(['id' => $invoiceIds, 'status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])->all() as $invoice) {
            $otherLines = InvoiceItem::find()->where(['invoice_id' => $invoice->id])
                ->andWhere(['not', ['item_type' => $itemType, 'item_id' => $itemId]])->exists();
            if ($otherLines) {
                $leftOpen[] = $invoice->invoice_number;
                continue;
            }
            try {
                (new InvoiceCancellationService())->cancel($invoice, 'Service disabled by staff.');
            } catch (\DomainException) {
                $leftOpen[] = $invoice->invoice_number; // e.g. a payment already made: staff decide (refund)
            }
        }

        return $leftOpen === [] ? null : 'Renewal invoice ' . implode(', ', $leftOpen) . ' still bills this service (it also covers other items or has a payment). Edit, cancel or refund it if the customer should not pay for it.';
    }
}
