<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
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
     * @return int unpaid renewals released, which the next billing run bills again unless the service is disabled
     */
    public function closeAsRefunded(Invoice $invoice, ?int $keptTransactionId = null): int
    {
        $blocker = $this->closeBlocker($invoice, $keptTransactionId);
        if ($blocker !== null) {
            throw new \DomainException($blocker);
        }
        $invoice->status = Invoice::STATUS_REFUNDED;
        $invoice->updated_at = time();
        if (!$invoice->save(false, ['status', 'updated_at'])) {
            throw new \RuntimeException('Unable to close the invoice as refunded.');
        }
        // As cancelling does: the coupon use shouldn't count against its limits for the re-billed invoice.
        // Money kept on the invoice means the customer did use the discount.
        if (!empty($invoice->coupon_code) && $this->getCapturedAmount((int) $invoice->id) <= 0) {
            (new CouponService())->removeForInvoice((int) $invoice->id);
        }
        // Release the billed periods (this invoice's and those carried into it) so the next
        // billing run invoices them again, as cancelling does. Fulfilled renewals stay as they are.
        return BillingEvent::updateAll(
            ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => 'Invoice closed as refunded.', 'updated_at' => time()],
            ['invoice_id' => $this->withCarriedInvoiceIds((int) $invoice->id), 'status' => [BillingEvent::STATUS_PENDING, BillingEvent::STATUS_INVOICED]]
        );
    }

    /**
     * Why the invoice can't be closed as refunded, or null. $refundingTransactionId: the payment being
     * refunded now (fully, or partly with the rest kept by agreement), checked before any money is sent.
     */
    public function closeBlocker(Invoice $invoice, ?int $refundingTransactionId = null): ?string
    {
        if ($this->getCapturedAmount((int) $invoice->id, $refundingTransactionId) > 0) {
            return 'Other payments on this invoice are still kept, so it can\'t be closed as refunded.';
        }
        // Closing releases the periods of invoices carried into this one too; money kept on those would
        // then be stranded while the period is billed again in full.
        foreach ($this->withCarriedInvoiceIds((int) $invoice->id) as $sourceId) {
            if ($sourceId !== (int) $invoice->id && $this->getCapturedAmount($sourceId) > 0) {
                $number = Invoice::find()->select('invoice_number')->where(['id' => $sourceId])->scalar();
                return 'Earlier invoice ' . $number . ' (carried into this one) still has payments kept on it, so this invoice can\'t be closed as refunded. Use "Refund (Still Owed)".';
            }
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

    /** The open invoice a carried-forward invoice's balance now sits on (followed forward along arrears lines), or null. */
    public function openInvoiceCarrying(int $invoiceId): ?Invoice
    {
        for ($id = $invoiceId, $hops = 0; $hops < 10; $hops++) {
            $newer = Invoice::find()->alias('i')
                ->innerJoin(['ii' => InvoiceItem::tableName()], 'ii.invoice_id = i.id')
                ->where(['ii.item_type' => InvoiceItem::TYPE_ARREARS, 'ii.source_invoice_id' => $id])
                ->one();
            if ($newer === null || in_array($newer->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
                return $newer;
            }
            $id = (int) $newer->id;
        }

        return null;
    }

    /** Refuses a new payment on an invoice that is already settled or closed. */
    public function assertPayable(Invoice $invoice): void
    {
        if ($invoice->status === Invoice::STATUS_PAID || in_array($invoice->status, self::CLOSED_STATUSES, true)) {
            throw new \DomainException('Invoice ' . $invoice->invoice_number . ' is already ' . (Invoice::getStatuses()[$invoice->status] ?? $invoice->status) . '. Reject this payment, or refund it if the money was received.');
        }
    }
}
