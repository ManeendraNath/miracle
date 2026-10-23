<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\ServiceRenewalHistory;
use Yii;

final class InvoiceCancellationService
{

    public function __construct(
            private readonly InvoicePaymentService $paymentService = new InvoicePaymentService()
    )
    {
        
    }

    public function cancel(Invoice $invoice, ?string $reason = null): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $invoiceId = Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $invoice->id)->queryScalar();
            $lockedInvoice = $invoiceId !== false ? Invoice::findOne((int) $invoiceId) : null;
            if ($lockedInvoice === null) {
                throw new \RuntimeException('Invoice no longer exists.');
            }

            if ($lockedInvoice->status === Invoice::STATUS_CANCELLED) {
                $transaction->commit();
                return;
            }

            if ($lockedInvoice->status === Invoice::STATUS_CARRIED_FORWARD) {
                throw new \DomainException('A carried-forward invoice cannot be cancelled.');
            }

            $capturedAmount = $this->paymentService->getCapturedAmount((int) $lockedInvoice->id);

            if ($capturedAmount > 0) {
                throw new \DomainException('An invoice with captured payments cannot be cancelled. Use the refund workflow instead.');
            }

            $hasArrears = InvoiceItem::find()->where(['invoice_id' => $lockedInvoice->id, 'item_type' => InvoiceItem::TYPE_ARREARS])->exists();

            if ($hasArrears) {
                throw new \DomainException('An invoice containing carried-forward balances cannot be cancelled using the standard cancellation workflow.');
            }

            $hasFulfilledEvents = BillingEvent::find()->where(['invoice_id' => $lockedInvoice->id, 'status' => BillingEvent::STATUS_FULFILLED])->exists();

            if ($hasFulfilledEvents) {
                throw new \DomainException('Invoice contains fulfilled renewals and cannot be cancelled.');
            }

            $hasRenewalHistory = ServiceRenewalHistory::find()->where(['invoice_id' => $lockedInvoice->id])->exists();

            if ($hasRenewalHistory) {
                throw new \DomainException('Invoice already has renewal history and cannot be cancelled.');
            }

            $now = time();
            $notes = $reason !== null && trim($reason) !== '' ? mb_substr(trim($reason), 0, 500) : 'Invoice cancelled.';
            BillingEvent::updateAll(
                    ['status' => BillingEvent::STATUS_CANCELLED, 'notes' => $notes, 'updated_at' => $now],
                    [
                        'and', ['invoice_id' => $lockedInvoice->id],
                        [
                            'status' => [BillingEvent::STATUS_PENDING, BillingEvent::STATUS_INVOICED],
                        ],
                    ]
            );
            $lockedInvoice->status = Invoice::STATUS_CANCELLED;
            $lockedInvoice->updated_at = $now;
            if (!$lockedInvoice->save(false, ['status', 'updated_at'])) {
                throw new \RuntimeException('Unable to cancel invoice.');
            }
            if (!empty($lockedInvoice->coupon_code)) {
                (new CouponService())->removeForInvoice((int) $invoice->id);
            }
            $transaction->commit();

            $invoice->refresh();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }

            throw $e;
        }
    }
}
