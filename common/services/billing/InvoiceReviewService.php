<?php

declare(strict_types=1);

namespace common\services\billing;

use common\components\CustomYii;
use common\models\Invoice;
use common\services\notification\NotificationService;
use Yii;

/**
 * Renewal invoices can wait for a staff check before the customer sees them (site setting review_invoices).
 * A waiting invoice is a Draft: not in the portal, not emailed, no reminders, not carried forward, can't be paid.
 */
final class InvoiceReviewService
{
    public static function isOn(): bool
    {
        // getSetting() returns the default for an empty value, so the default must be "off".
        return (bool) CustomYii::getSetting('review_invoices', 0);
    }

    /**
     * Makes a Draft payable, dated today, and tells the customer. The due date stays the service's expiry date.
     *
     * @return bool whether the email went out (if not, staff can use Resend Invoice)
     */
    public function release(Invoice $invoice): bool
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $id = $db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE', [':id' => (int) $invoice->id])->queryScalar();
            $locked = $id !== false ? Invoice::findOne((int) $id) : null;
            if ($locked === null || $locked->status !== Invoice::STATUS_DRAFT) {
                throw new \DomainException('Only an invoice waiting for review can be sent.');
            }
            // Dated the day it is sent (the invoice date, GST report and reminders all use created_at).
            // The due date stays the expiry date set when it was made (or what staff changed it to).
            // ponytail: the number keeps the financial year it was made in; a Draft held across 31 March keeps last year's series.
            // Overdue balances waited for this invoice to be sent (AutoInvoiceService skips them while it is held).
            if ($locked->billing_run_id !== null && (new OutstandingBalanceService())->transferForUser($locked) > 0) {
                (new AutoInvoiceService())->calculateInvoiceTotals($locked);
            }
            $locked->status = Invoice::STATUS_UNPAID;
            $locked->created_at = time();
            $locked->updated_at = time();
            if (!$locked->save(false, ['status', 'created_at', 'updated_at'])) {
                throw new \RuntimeException('Invoice could not be released.');
            }
            (new NotificationService())->create(
                (int) $locked->user_id,
                'invoice',
                'New Invoice Generated',
                'Invoice ' . $locked->invoice_number . ' has been generated.',
                '/invoice/view?number=' . rawurlencode($locked->invoice_number),
            );
            $transaction->commit();
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
        $invoice->refresh();
        try {
            (new InvoiceDeliveryService())->send($invoice);

            return true;
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Invoice email failed after review.', 'invoiceId' => (int) $invoice->id, 'error' => $e->getMessage()], __METHOD__);

            return false;
        }
    }
}
