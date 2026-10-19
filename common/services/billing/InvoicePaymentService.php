<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\Transaction;

final class InvoicePaymentService
{

    /**
     * Amount actually received.
     *
     * Only Captured transactions represent settled payments.
     */
    public function getCapturedAmount(
            int $invoiceId
    ): float
    {
        $amount = Transaction::find()
                ->where([
                    'invoice_id' => $invoiceId,
                    'status' => Transaction::STATUS_CAPTURED,
                ])
                ->sum('amount');

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
    ): float
    {
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
    ): void
    {
        /*
         * These invoices are no longer part of ordinary payment
         * reconciliation.
         */
        if (in_array(
                        $invoice->status,
                        [
                            Invoice::STATUS_CANCELLED,
                            Invoice::STATUS_CARRIED_FORWARD,
                        ],
                        true
                )) {
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

        if ($paid <= 0) {
            $status = Invoice::STATUS_UNPAID;
        } elseif ($paid < $total) {
            $status = Invoice::STATUS_PARTIALLY_PAID;
        } else {
            $status = Invoice::STATUS_PAID;
        }

        if ($invoice->status === $status) {
            return;
        }

        $invoice->status = $status;
        $invoice->updated_at = time();

        if (!$invoice->save(
                        false,
                        [
                            'status',
                            'updated_at',
                        ]
                )) {
            throw new \RuntimeException(
                            'Unable to update invoice payment status.'
                    );
        }
    }
}
