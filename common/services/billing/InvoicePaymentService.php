<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\BillingSettings;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use common\services\payment\RazorpayService;

final class InvoicePaymentService
{
    public const CLOSED_STATUSES = [Invoice::STATUS_CANCELLED, Invoice::STATUS_CARRIED_FORWARD, Invoice::STATUS_REFUNDED];

    /**
     * Amount actually received and kept: Captured payments less any partial refund. Unapplied
     * payments (taken after the invoice was settled or closed) are held for refund, not counted.
     */
    public function getCapturedAmount(
        int $invoiceId,
        ?int $exceptTransactionId = null
    ): float {
        $amount = Transaction::find()
                ->where([
                    'invoice_id' => $invoiceId,
                    'status' => Transaction::STATUS_CAPTURED,
                ])
                ->andWhere(['is_unapplied' => 0])
                ->andFilterWhere(['<>', 'id', $exceptTransactionId])
                ->sum('amount - refunded_amount');

        return round(
            (float) ($amount ?: 0),
            2
        );
    }

    /**
     * Remaining collectible balance.
     */
    public function getOutstanding(
        Invoice $invoice
    ): float {
        // Closed invoices owe nothing here (a carried-forward balance is owed on the newer invoice).
        if (in_array($invoice->status, self::CLOSED_STATUSES, true)) {
            return 0.00;
        }
        $paid = $this->getCapturedAmount(
            (int) $invoice->id
        );

        return max(
            0.00,
            round(
                (float) $invoice->total_payable - $paid,
                2
            )
        );
    }

    /**
     * Synchronise invoice.status with actual transactions.
     */
    public function reconcile(
        Invoice $invoice
    ): void {
        /*
         * These invoices are no longer part of ordinary payment
         * reconciliation.
         */
        if (
            in_array(
                $invoice->status,
                [
                            Invoice::STATUS_CANCELLED,
                            Invoice::STATUS_CARRIED_FORWARD,
                            // Closed by an admin with closeAsRefunded(): nothing more is owed.
                            Invoice::STATUS_REFUNDED,
                        ],
                true
            )
        ) {
            return;
        }

        $paid = $this->getCapturedAmount(
            (int) $invoice->id
        );

        $total = round(
            (float) $invoice->total_payable,
            2
        );

        if ($total <= 0) {
            throw new \DomainException(
                'Invoice total must be greater than zero '
                            . 'before processing payment.'
            );
        }

        if ($paid >= $total) {
            $status = Invoice::STATUS_PAID;
        } elseif ($paid <= 0) {
            $status = Invoice::STATUS_UNPAID;
        } else {
            $status = Invoice::STATUS_PARTIALLY_PAID;
        }

        if ($invoice->status === $status) {
            return;
        }

        $invoice->status = $status;
        $invoice->updated_at = time();

        if (
            !$invoice->save(
                false,
                [
                            'status',
                            'updated_at',
                        ]
            )
        ) {
            throw new \RuntimeException(
                'Unable to update invoice payment status.'
            );
        }
    }

    /**
     * After refunding everything, an admin can close the invoice: nothing more is owed, so the billing
     * run doesn't carry it forward and reminders stop. (A refund alone leaves it payable, e.g. when a
     * wrong payment was returned so the customer can pay again.)
     *
     * $writeOff: staff forgive the balance, so money already received stays and the close isn't refused for it.
     *
     * @return int unpaid renewals released, which the next billing run bills again unless the service is disabled
     */
    public function closeAsRefunded(Invoice $invoice, ?int $keptTransactionId = null, bool $writeOff = false): int
    {
        $blocker = $this->closeBlocker($invoice, $keptTransactionId, $writeOff);
        if ($blocker !== null) {
            throw new \DomainException($blocker);
        }
        $invoice->status = Invoice::STATUS_REFUNDED;
        $invoice->updated_at = time();
        if (!$invoice->save(false, ['status', 'updated_at'])) {
            throw new \RuntimeException('Unable to close the invoice as refunded.');
        }
        $invoiceIds = $this->withCarriedInvoiceIds((int) $invoice->id);
        // Nothing is owed on what was carried in either. Their renewal history points at them, so a
        // renewal with no money kept must stop counting as paid (RenewalFulfilmentService::fullyRefundedInvoices).
        Invoice::updateAll(['status' => Invoice::STATUS_REFUNDED, 'updated_at' => time()], ['id' => $invoiceIds, 'status' => Invoice::STATUS_CARRIED_FORWARD]);
        // As cancelling does: the coupon use shouldn't count against its limits for the re-billed invoice.
        // Money kept on the invoice means the customer did use the discount.
        foreach ($invoiceIds as $invoiceId) {
            if ($this->getCapturedAmount($invoiceId) <= 0) {
                (new CouponService())->removeForInvoice($invoiceId);
            }
        }
        // Release the billed periods (this invoice's and those carried into it) so the next
        // billing run invoices them again, as cancelling does. Fulfilled renewals stay as they are.
        return BillingEvent::updateAll(
            ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Invoice closed as refunded.', 'updated_at' => time()],
            ['invoice_id' => $invoiceIds, 'status' => [BillingEvent::STATUS_PENDING, BillingEvent::STATUS_INVOICED]]
        );
    }

    /**
     * Why the invoice can't be closed as refunded, or null. $refundingTransactionId: the payment being
     * refunded now (fully, or partly with the rest kept by agreement), checked before any money is sent.
     */
    public function closeBlocker(Invoice $invoice, ?int $refundingTransactionId = null, bool $writeOff = false): ?string
    {
        if (!$writeOff && $this->getCapturedAmount((int) $invoice->id, $refundingTransactionId) > 0) {
            return 'Other payments on this invoice are still kept, so it can\'t be closed as refunded. Use Write Off to forgive the balance and keep them.';
        }

        return $this->hasPendingProof((int) $invoice->id) ? 'A payment on this invoice is still awaiting review. Approve or reject it first.' : null;
    }

    /** A payment awaiting review (UPI/bank proof or an admin-entered pending payment); unpaid online orders don't count. */
    public function hasPendingProof(int $invoiceId): bool
    {
        return Transaction::find()->where(['invoice_id' => $invoiceId, 'status' => Transaction::STATUS_PENDING])
            ->andWhere(RazorpayService::notUnpaidOrderCondition())->exists();
    }

    /** The invoice plus every invoice carried into it (followed back along arrears lines). @return int[] */
    private function withCarriedInvoiceIds(int $invoiceId): array
    {
        $invoiceIds = [$invoiceId];
        for ($ids = $invoiceIds; $ids !== [];) {
            $ids = array_map('intval', InvoiceItem::find()->select('source_invoice_id')
                ->where(['invoice_id' => $ids, 'item_type' => InvoiceItem::TYPE_ARREARS])
                ->andWhere(['not', ['source_invoice_id' => null]])->andWhere(['not', ['source_invoice_id' => $invoiceIds]])->column());
            $invoiceIds = [...$invoiceIds, ...$ids];
        }

        return $invoiceIds;
    }

    /** The payable invoice that bills this service: its own, or the newer one its balance was carried into. */
    public function payableInvoiceForService(int $userId, int $itemType, int $itemId): ?Invoice
    {
        $find = static fn(array $statuses): ?Invoice => Invoice::find()
            ->alias('i')
            ->innerJoin(['ii' => InvoiceItem::tableName()], 'ii.invoice_id = i.id')
            ->where(['i.user_id' => $userId, 'ii.item_type' => $itemType, 'ii.item_id' => $itemId, 'i.status' => $statuses])
            ->orderBy(['i.created_at' => SORT_DESC, 'i.id' => SORT_DESC])
            ->one();
        $invoice = $find([Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]);
        if ($invoice !== null) {
            return $invoice;
        }
        $carried = $find([Invoice::STATUS_CARRIED_FORWARD]);

        return $carried !== null ? $this->openInvoiceCarrying((int) $carried->id) : null;
    }

    /**
     * What a customer can do about a service nearing expiry. Renewal invoices only exist from
     * invoice_before_days before expiry, and only for active, priced services.
     *
     * @param \common\models\Domains|\common\models\Hosting|\common\models\Maintenance $service
     * @return array{invoiceNumber: string|null, billable: bool, issuedFrom: string}
     */
    public function renewalState($service, int $itemType): array
    {
        $settings = BillingSettings::forType($itemType);
        $days = max(0, (int) $settings->invoice_before_days);

        return [
            'invoiceNumber' => $this->payableInvoiceForService((int) $service->user_id, $itemType, (int) $service->id)->invoice_number ?? null,
            'billable' => (bool) $settings->is_active && (int) $service->status === 1 && (float) $service->renewal_base_amount > 0,
            'issuedFrom' => date('Y-m-d', (int) strtotime($service->current_expiry_date . " -{$days} days")),
        ];
    }

    /** The open invoice a carried-forward invoice's balance now sits on (followed forward along arrears lines), or null. */
    public function openInvoiceCarrying(int $invoiceId): ?Invoice
    {
        // Each step goes to a newer invoice, so the chain can't loop.
        for ($id = $invoiceId;;) {
            $newer = Invoice::find()->alias('i')
                ->innerJoin(['ii' => InvoiceItem::tableName()], 'ii.invoice_id = i.id')
                ->where(['ii.item_type' => InvoiceItem::TYPE_ARREARS, 'ii.source_invoice_id' => $id])
                ->one();
            if ($newer === null || in_array($newer->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
                return $newer;
            }
            $id = (int) $newer->id;
        }
    }

    /** Refuses a new payment on an invoice that is already settled or closed. */
    public function assertPayable(Invoice $invoice): void
    {
        if ($invoice->status === Invoice::STATUS_PAID || in_array($invoice->status, self::CLOSED_STATUSES, true)) {
            throw new \DomainException('Invoice ' . $invoice->invoice_number . ' is already ' . (Invoice::getStatuses()[$invoice->status] ?? $invoice->status) . '. Reject this payment, or refund it if the money was received.');
        }
    }
}
