<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use Yii;

/**
 * A customer applying / removing a coupon on their own unpaid invoice (customer portal).
 * Coupon rules live in CouponService; totals are recomputed by the same code that builds
 * automatic invoices, so arrears lines are never discounted or taxed again.
 */
final class InvoiceCouponService
{
    public function __construct(
        private readonly CouponService $coupons = new CouponService(),
        private readonly AutoInvoiceService $totals = new AutoInvoiceService()
    ) {
    }

    /** Why the customer can't change the coupon on this invoice now, or null when they can. */
    public function blockReason(Invoice $invoice): ?string
    {
        if ($invoice->status !== Invoice::STATUS_UNPAID) {
            return 'Coupons can only be used on unpaid invoices.';
        }
        // Any payment, proof under review or started online payment (a Razorpay order is fixed
        // at the old amount and could still be paid) freezes the total.
        // ponytail: an abandoned Razorpay order blocks coupons on that invoice for good; add a
        // checkout `timeout` + recency window if customers hit it.
        $paymentStarted = Transaction::find()
            ->where(['invoice_id' => (int) $invoice->id])
            ->andWhere(['<>', 'status', Transaction::STATUS_FAILED])
            ->exists();
        if ($paymentStarted) {
            return 'Coupons cannot be changed once a payment has been made or started for this invoice.';
        }
        if (trim((string) $invoice->coupon_code) === '' && (float) $invoice->discount_amount > 0) {
            return 'This invoice already has a discount, so a coupon cannot be added.';
        }
        return null;
    }

    public function apply(Invoice $invoice, string $code): Invoice
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            throw new \DomainException('Please enter a coupon code.');
        }
        return $this->change($invoice, function (Invoice $locked) use ($code): void {
            if (trim((string) $locked->coupon_code) !== '') {
                throw new \DomainException('This invoice already has a coupon. Remove it first.');
            }
            $totalsByType = [];
            foreach (InvoiceItem::find()->where(['invoice_id' => $locked->id])->all() as $item) {
                if ((int) $item->item_type !== InvoiceItem::TYPE_ARREARS) {
                    $totalsByType[(int) $item->item_type] = round(($totalsByType[(int) $item->item_type] ?? 0) + (float) $item->total_price, 2);
                }
            }
            // Current charges only: arrears are never discounted, even by an unrestricted coupon.
            $locked->discount_amount = $this->coupons->calculate($code, (int) $locked->user_id, array_sum($totalsByType), (int) $locked->id, false, $totalsByType);
            $locked->coupon_code = $code;
        });
    }

    public function remove(Invoice $invoice): Invoice
    {
        return $this->change($invoice, function (Invoice $locked): void {
            if (trim((string) $locked->coupon_code) === '') {
                throw new \DomainException('This invoice has no coupon.');
            }
            $locked->discount_amount = 0.00;
            $locked->coupon_code = null;
            $locked->coupon_id = null;
        });
    }

    private function change(Invoice $invoice, callable $edit): Invoice
    {
        $db = Yii::$app->db;
        $dbTransaction = $db->beginTransaction();
        try {
            $id = $db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE', [':id' => (int) $invoice->id])->queryScalar();
            $locked = $id !== false ? Invoice::findOne((int) $id) : null;
            if ($locked === null) {
                throw new \DomainException('Invoice does not exist.');
            }
            $reason = $this->blockReason($locked);
            if ($reason !== null) {
                throw new \DomainException($reason);
            }
            $edit($locked);
            $this->totals->calculateInvoiceTotals($locked);
            if ((float) $locked->total_payable <= 0) {
                throw new \DomainException('Invoice total must be greater than zero.');
            }
            if ($locked->coupon_code !== null) {
                $this->coupons->record($locked);
            } else {
                $this->coupons->removeForInvoice((int) $locked->id);
            }
            $dbTransaction->commit();
            return $locked;
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            throw $e;
        }
    }
}
