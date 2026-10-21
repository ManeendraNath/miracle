<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\Transaction;
use Yii;

final class PaymentProcessingService
{

    public function __construct(
            private readonly InvoicePaymentService $paymentService = new InvoicePaymentService(),
            private readonly RenewalFulfilmentService $fulfilmentService = new RenewalFulfilmentService()
    )
    {
        
    }

    public function transactionChanged(
            Transaction $paymentTransaction,
            bool $useTransaction = true
    ): void
    {
        if ((int) $paymentTransaction->invoice_id <= 0) {
            throw new \InvalidArgumentException(
                            'Transaction must belong to an invoice.'
                    );
        }

        $dbTransaction = $useTransaction ? Yii::$app->db->beginTransaction() : null;

        try {
            $invoiceId = Yii::$app->db
                    ->createCommand(
                            '
                        SELECT id
                        FROM {{%invoice}}
                        WHERE id = :id
                        FOR UPDATE
                        '
                    )
                    ->bindValue(
                            ':id',
                            (int) $paymentTransaction
                            ->invoice_id
                    )
                    ->queryScalar();

            $invoice = $invoiceId !== false ? Invoice::findOne(
                            (int) $invoiceId
                    ) : null;

            if ($invoice === null) {
                throw new \RuntimeException(
                                'Invoice not found for transaction.'
                        );
            }

            if (
                    $invoice->status ===
                    Invoice::STATUS_CANCELLED
            ) {
                throw new \DomainException(
                                'Payment cannot be applied to '
                                . 'a cancelled invoice.'
                        );
            }

            if (
                    $invoice->status ===
                    Invoice::STATUS_CARRIED_FORWARD
            ) {
                throw new \DomainException(
                                'This invoice balance has already '
                                . 'been carried forward.'
                        );
            }

            // Recalculate from the complete captured-payment ledger.
            $this->paymentService->reconcile(
                            $invoice
                    );

            // Renewal fulfilment happens only after full payment.
            if (
                    $invoice->status ===
                    Invoice::STATUS_PAID
            ) {
                $this->fulfilmentService
                        ->fulfilInvoice(
                                $invoice,
                                false
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

            Yii::error(['message' => 'Payment processing failed.', 'transactionId' => (int) $paymentTransaction->id, 'invoiceId' => (int) $paymentTransaction->invoice_id, 'error' => $e->getMessage()], __METHOD__);

            throw $e;
        }
    }
}
