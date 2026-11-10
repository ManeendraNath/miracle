<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\TransactionSearch;
use common\models\Invoice;
use common\models\Transaction;
use common\models\TransactionRefund;
use common\services\admin\AdminAlertService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\PaymentConfirmationService;
use common\services\billing\PaymentProcessingService;
use common\services\payment\RazorpayService;
use Yii;
use yii\web\NotFoundHttpException;
use common\services\notification\NotificationService;
use yii\web\BadRequestHttpException;

/**
 * TransactionController
 *
 * Handles transaction administration.
 *
 * Important:
 * - Invoice payment status is NOT changed directly here.
 * - PaymentProcessingService recalculates invoice status from
 *   captured transactions.
 * - Financial transaction records are never physically deleted.
 */
class TransactionController extends BaseController
{
    /**
     * Lists all transactions.
     */
    public function actionIndex()
    {
        $searchModel = new TransactionSearch();

        $dataProvider = $searchModel->search(
            $this->request->queryParams
        );

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single transaction.
     */
    public function actionView(int $id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Create a transaction.
     *
     * This supports:
     * - Manual transactions
     * - Bank deposits
     * - UPI payments
     * - Gateway transactions entered from admin
     *
     * If the transaction is created directly as Captured,
     * invoice reconciliation happens immediately.
     */
    public function actionCreate()
    {
        $model = new Transaction(['scenario' => Transaction::SCENARIO_ADMIN_ENTRY]);

        if ($this->request->isPost && $model->load($this->request->post())) {
            try {
                $model->status = Transaction::STATUS_PENDING;
                $model->paid_at = null;
                $model->gateway_order_id = null;
                $model->gateway_fee = 0.00;
                $model->raw_payload = null;
                $model->created_at = time();
                $model->updated_at = time();
                $this->saveAndProcessPayment($model);
                Yii::$app->session->setFlash('success', 'Transaction created successfully.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        } else {
            $model->loadDefaultValues();
        }
        return $this->render('create', ['model' => $model, 'invoiceList' => $this->getInvoiceList()]);
    }

    /**
     * Update an existing transaction.
     *
     * Any status/amount change causes invoice payment
     * reconciliation.
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        /*
         * Captured/refunded financial records should not normally
         * be edited through general CRUD.
         *
         * Captured payments should go through explicit financial
         * workflows such as refund rather than arbitrary editing.
         */
        if (in_array($model->status, [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED], true)) {
            Yii::$app->session->setFlash('error', 'Captured or refunded transactions cannot be edited through general transaction editing.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        if ($model->status !== Transaction::STATUS_PENDING) {
            throw new BadRequestHttpException('Only pending transactions can be modified.');
        }
        if ($model->isOnline()) {
            Yii::$app->session->setFlash('error', 'Online payments (Razorpay, PayPal) are managed by the gateway and cannot be edited.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        // Status, paid_at etc. are not editable here: approve / reject / refund handle them.
        $model->scenario = Transaction::SCENARIO_ADMIN_ENTRY;
        if ($this->request->isPost && $model->load($this->request->post())) {
            try {
                $this->saveAndProcessPayment($model);
                Yii::$app->session->setFlash('success', 'Transaction updated successfully.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->render('update', ['model' => $model, 'invoiceList' => $this->getInvoiceList()]);
    }

    /**
     * Approve a user-submitted manual payment.
     *
     * IMPORTANT:
     *
     * This DOES NOT directly mark the invoice Paid.
     *
     * Instead:
     *
     * Transaction -> Captured
     *      ↓
     * PaymentProcessingService
     *      ↓
     * SUM(all Captured transactions)
     *      ↓
     * Unpaid / Partially_Paid / Paid
     *      ↓
     * Paid -> renewal fulfilment
     */
    public function actionApprove(int $id)
    {
        $paymentTransaction = $this->findModel($id);
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            // Lock and re-read so a concurrent reject can't be overwritten to Captured.
            Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', $id)->queryScalar();
            $paymentTransaction->refresh();
            if ($paymentTransaction->status !== Transaction::STATUS_PENDING) {
                throw new \DomainException('Only pending payments can be approved.');
            }
            // Online orders are confirmed by Razorpay itself; a pending one was never paid.
            if ($paymentTransaction->isOnline()) {
                throw new \DomainException('Online payments (Razorpay, PayPal) are confirmed automatically and cannot be approved manually.');
            }
            $lockedInvoiceId = Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $paymentTransaction->invoice_id)->queryScalar();
            $invoice = $lockedInvoiceId !== false ? Invoice::findOne((int) $lockedInvoiceId) : null;
            if ($invoice === null) {
                throw new \DomainException('This payment has no invoice.');
            }
            // E.g. paid online meanwhile. The money did arrive, so as in RazorpayService::capture() it is recorded
            // but held off the invoice, for staff to refund or apply. Reject is there for a proof that isn't real.
            $unapplied = in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_DRAFT, ...InvoicePaymentService::CLOSED_STATUSES], true);
            $now = time();
            $paymentTransaction->status = Transaction::STATUS_CAPTURED;
            $paymentTransaction->paid_at = $now;
            $paymentTransaction->updated_at = $now;
            if ($unapplied) {
                $paymentTransaction->is_unapplied = 1;
                $paymentTransaction->user_payment_notes = trim($paymentTransaction->user_payment_notes . "\n" . Transaction::UNAPPLIED_NOTE_PREFIX . ' invoice was ' . $invoice->statusLabel() . ' when approved.');
            }
            if (!$paymentTransaction->save(false, ['status', 'paid_at', 'updated_at', 'is_unapplied', 'user_payment_notes'])) {
                throw new \RuntimeException('Payment could not be approved.');
            }
            if (!$unapplied) {
                (new PaymentProcessingService())->transactionChanged($paymentTransaction, false);
            }
            $excess = $unapplied ? 0.0 : (new InvoicePaymentService())->getCapturedAmount((int) $invoice->id) - (float) $invoice->total_payable;
            $dbTransaction->commit();
            if ($unapplied) {
                Yii::$app->session->setFlash('warning', 'Invoice ' . $invoice->reference() . ' could not accept this payment (' . $invoice->statusLabel() . '), so it is held on the payment. Refund it or apply it to another invoice.');
                try {
                    AdminAlertService::paymentApprovedOnClosedInvoice($invoice->reference(), $invoice->statusLabel(), (float) $paymentTransaction->amount, (int) $paymentTransaction->id);
                    (new NotificationService())->create((int) $invoice->user_id, 'payment', 'Payment Received: Not Yet Applied', 'We received your payment of ₹' . number_format((float) $paymentTransaction->amount, 2) . ' for ' . $invoice->customerReference() . ', which can no longer take payments. We will refund it or apply it to another invoice.', '/payment/view?id=' . (int) $paymentTransaction->id);
                    (new PaymentConfirmationService())->sendHeld($invoice, (float) $paymentTransaction->amount);
                } catch (\Throwable $alertError) {
                    Yii::error(['message' => 'Unapplied approval alert failed.', 'transactionId' => $id, 'error' => $alertError->getMessage()], __METHOD__);
                }

                return $this->redirect(['view', 'id' => $paymentTransaction->id]);
            }
            // E.g. a proof submitted for the full balance, approved after another payment was recorded.
            if (round($excess, 2) > 0) {
                try {
                    AdminAlertService::paymentOverpaid($invoice->invoice_number, (float) $paymentTransaction->amount, round($excess, 2), (string) $paymentTransaction->gateway_payment_id, false);
                } catch (\Throwable $alertError) {
                    Yii::error(['message' => 'Overpayment alert failed.', 'transactionId' => $id, 'error' => $alertError->getMessage()], __METHOD__);
                }
                Yii::$app->session->setFlash('warning', 'This approval overpays invoice ' . $invoice->invoice_number . ' by ₹' . number_format($excess, 2) . '. Refund the difference to the customer.');
            }
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Payment approval failed.', 'transactionId' => (int) $paymentTransaction->id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('error', $e instanceof \DomainException ? $e->getMessage() : 'Payment could not be approved.');
            return $this->redirect(['view', 'id' => $paymentTransaction->id]);
        }

        try {
            // Notification first: a mail failure must not cost the customer both.
            (new NotificationService())->create((int) $paymentTransaction->invoice->user_id, 'payment', 'Payment Confirmed', 'Your payment for invoice ' . $paymentTransaction->invoice->invoice_number . ' has been confirmed.', '/invoice/view?number=' . rawurlencode($paymentTransaction->invoice->invoice_number));
            (new PaymentConfirmationService())->send($paymentTransaction);
            Yii::$app->session->setFlash('success', 'Payment approved and confirmation email sent.');
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment was approved but confirmation email failed.', 'transactionId' => (int) $paymentTransaction->id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->addFlash('warning', 'Payment approved successfully, but the confirmation email could not be sent.');
        }
        return $this->redirect(['view', 'id' => $paymentTransaction->id]);
    }

    /**
     * Refunds all or part of a payment (POST refund_amount, default: what is left) and recalculates the
     * invoice from what is kept. Razorpay payments are refunded through the Razorpay API (or only recorded
     * when the dashboard already refunded that amount); UPI / bank refunds are returned by hand first.
     * Every check runs before Razorpay is called, so a refusal never follows money that was sent.
     */
    public function actionRefund(int $id)
    {
        $close = (bool) $this->request->post('close_invoice');
        $dbTransaction = Yii::$app->db->beginTransaction();
        $refundId = null;
        try {
            // Lock order as approve and capture: transaction row, then invoice.
            Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', $id)->queryScalar();
            $model = $this->findModel($id);
            if ($model->status !== Transaction::STATUS_CAPTURED) {
                throw new \DomainException('Only captured payments can be refunded.');
            }
            Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $model->invoice_id)->queryScalar();
            $invoice = Invoice::findOne((int) $model->invoice_id);

            $remaining = round((float) $model->amount - (float) $model->refunded_amount, 2);
            $input = trim(self::scalarParam($this->request->post('refund_amount', '')));
            $amount = $input === '' ? $remaining : round((float) $input, 2);
            $expected = $this->request->post('refunded_before');
            // A confirm submits the form by script, past the field's "required": a cleared box must not mean "refund everything".
            if (($input === '' && $expected !== null) || ($input !== '' && !is_numeric($input)) || $amount <= 0 || $amount > $remaining) {
                throw new \DomainException('Enter a refund amount between ₹0.01 and ₹' . number_format($remaining, 2) . '.');
            }
            $full = abs($amount - $remaining) < 0.005;
            // The form carries what was refunded when it was shown: a second tab or a double click with the
            // same partial amount would otherwise pass every check again and send it twice.
            if ($expected !== null && abs((float) $expected - (float) $model->refunded_amount) >= 0.005) {
                throw new \DomainException('This payment was refunded meanwhile (now ₹' . number_format((float) $model->refunded_amount, 2) . '). Check the amount and try again.');
            }

            // ponytail: the Razorpay checks below run under these row locks (up to 60 s if it is slow). Refunds are rare and
            // staff-driven; moved out of the lock, two tabs could both see "nothing refunded yet".
            $paymentService = new InvoicePaymentService();
            // Its unpaid balance moved to a newer invoice as a fixed arrears line; refunding it while that is
            // open would leave the line short. Once settled nothing is left to get wrong. An unapplied
            // payment was never part of the arrears, so it can always go back.
            // A refund already made in the Razorpay dashboard, or in PayPal (always refunded there first), can still be
            // recorded: that money has gone either way.
            // ponytail: refused while open; raise the arrears on the newer invoice if this case gets common.
            $carrying = ($invoice->status ?? null) === Invoice::STATUS_CARRIED_FORWARD && !$model->isUnapplied() ? $paymentService->openInvoiceCarrying((int) $invoice->id) : null;
            $razorpay = new RazorpayService();
            $viaRazorpay = (int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && $razorpay->isEnabled();
            if ($carrying !== null && (int) $model->gateway_id !== Transaction::GATEWAY_PAYPAL && !($viaRazorpay && $razorpay->isAlreadyRefunded((string) $model->gateway_payment_id, (int) round($amount * 100), (int) round((float) $model->refunded_amount * 100)))) {
                throw new \DomainException('This invoice was carried forward into ' . $carrying->invoice_number . ', so its payments cannot be refunded until that invoice is paid or written off.');
            }
            $canReconcile = $invoice !== null && !in_array($invoice->status, InvoicePaymentService::CLOSED_STATUSES, true);
            // Closing after a partial refund keeps the rest by agreement (e.g. a domain fee): nothing more is owed.
            if ($close) {
                $blocker = $canReconcile ? $paymentService->closeBlocker($invoice, (int) $model->id) : null;
                if ($blocker !== null) {
                    throw new \DomainException($blocker);
                }
            }

            if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && !$razorpay->isEnabled() && !$this->request->post('manual_return')) {
                throw new \DomainException('Razorpay is turned off here, so this would only write the record and send nothing. Return the money yourself first, then tick the box to confirm.');
            }

            $note = 'Refunded ₹' . number_format($amount, 2) . ' on ' . date('d M Y') . '.';
            if ($viaRazorpay) {
                $refundId = $razorpay->refundPayment((string) $model->gateway_payment_id, (int) round($amount * 100), (int) round((float) $model->refunded_amount * 100));
                // Kept even if the save below fails: the money has gone.
                Yii::warning(['message' => 'Razorpay refund sent.', 'transactionId' => $id, 'refundId' => $refundId, 'amount' => $amount], __METHOD__);
                $note = 'Refunded ₹' . number_format($amount, 2) . ($refundId !== null ? ' through Razorpay: ' . $refundId : ' (refunded in the Razorpay dashboard)') . '.';
            }
            $model->refunded_amount = round((float) $model->refunded_amount + $amount, 2);
            $model->refunded_at = time();
            $model->status = $full ? Transaction::STATUS_REFUNDED : Transaction::STATUS_CAPTURED;
            $model->user_payment_notes = trim($model->user_payment_notes . "\n" . $note);
            $model->updated_at = time();
            if (!$model->save(false, ['refunded_amount', 'refunded_at', 'status', 'user_payment_notes', 'updated_at'])) {
                throw new \RuntimeException('Refund could not be recorded.');
            }
            (new TransactionRefund(['transaction_id' => (int) $model->id, 'amount' => $amount, 'refunded_at' => (int) $model->refunded_at, 'note' => $refundId]))->save(false);

            $flash = 'success';
            // The money kept when the invoice was closed went back too: the discount is no longer used.
            if (($invoice->status ?? null) === Invoice::STATUS_REFUNDED) {
                $paymentService->releaseCoupons($invoice);
            }
            $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. The invoice was ' . ($invoice !== null ? $invoice->statusLabel() : '-') . ', so it is unchanged and nothing more is owed on it.' . ($model->isUnapplied() ? '' : ' Renewals already granted are not reversed: disable the service if needed.');
            if ($carrying !== null) {
                $flash = 'warning';
                $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. Invoice ' . $carrying->invoice_number . ' carries the old balance, which does not include this amount: bill it separately if it is still owed.';
            }
            if ($canReconcile) {
                (new PaymentProcessingService())->transactionChanged($model, false);
                $invoice->refresh();
                if ($close) {
                    $released = $paymentService->closeAsRefunded($invoice, (int) $model->id);
                    $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded and the invoice closed: nothing more is owed.' . ($full ? '' : ' The rest of the payment is kept.');
                    if ($released > 0) {
                        $flash = 'warning';
                        $message .= ' Its unpaid renewal is billed again in full on the next run' . ($full ? '' : ', with no credit for the money kept,') . ' if the service is Active; a suspended or overdue service is not billed until it is set Active.';
                    }
                } elseif ($model->isUnapplied()) {
                    $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. This payment was not counted on the invoice, so its balance is unchanged.';
                } elseif ($invoice->status === Invoice::STATUS_PAID) {
                    $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. Other payments still cover the invoice, so it stays Paid.';
                } else {
                    $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. The invoice balance is due again.';
                }
            }
            $dbTransaction->commit();
            Yii::$app->session->setFlash($flash, $message);
            if ($invoice !== null) {
                // Recorded: a failed message must not look like a failed refund.
                try {
                    (new NotificationService())->create((int) $invoice->user_id, 'payment', 'Payment Refunded', '₹' . number_format($amount, 2) . ' for ' . $invoice->customerReference() . ' has been refunded.', '/payment/view?id=' . (int) $model->id);
                    (new PaymentConfirmationService())->sendRefund(Invoice::findOne((int) $invoice->id), $amount, $viaRazorpay || (int) $model->gateway_id === Transaction::GATEWAY_PAYPAL, (int) $model->gateway_id === Transaction::GATEWAY_PAYPAL);
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Refund was recorded but the customer could not be told.', 'transactionId' => $id, 'error' => $e->getMessage()], __METHOD__);
                    Yii::$app->session->addFlash('warning', 'The refund is recorded, but the email to the customer could not be sent.');
                }
            }
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Refund recording failed.', 'transactionId' => $id, 'refundId' => $refundId, 'error' => $e->getMessage()], __METHOD__);
            $sent = $refundId !== null ? ' Razorpay HAS refunded the money (' . $refundId . '): press Refund again with the same amount to record it.' : '';
            Yii::$app->session->setFlash('error', ($e instanceof \DomainException ? $e->getMessage() : 'Refund could not be recorded.') . $sent);
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Reject an invalid manual payment.
     */
    public function actionReject(int $id)
    {
        $dbTransaction = Yii::$app->db->beginTransaction();

        try {
            $transactionId = Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', $id)->queryScalar();
            $model = $transactionId !== false ? Transaction::findOne((int) $transactionId) : null;

            if ($model === null) {
                throw new NotFoundHttpException('Transaction does not exist.');
            }
            if ($model->status !== Transaction::STATUS_PENDING) {
                throw new \DomainException('Only pending payments can be rejected.');
            }
            // A pending online order may still be paid in the customer's open checkout;
            // failing it here would make that capture unrecordable.
            if ($model->isOnline()) {
                throw new \DomainException('Online orders (Razorpay, PayPal) are settled automatically and cannot be rejected manually.');
            }
            $reference = (string) $model->gateway_payment_id;
            $model->status = Transaction::STATUS_FAILED;
            // Frees the reference (unique) so the customer can resubmit the genuine one; still readable here.
            if ((string) $model->gateway_payment_id !== '') {
                $model->gateway_payment_id = substr('REJECTED-' . $model->id . '-' . $model->gateway_payment_id, 0, 100);
            }
            $model->updated_at = time();
            if (!$model->save(false, ['status', 'gateway_payment_id', 'updated_at'])) {
                throw new \RuntimeException('Payment could not be rejected.');
            }
            $dbTransaction->commit();
            Yii::$app->session->setFlash('success', 'Payment proof rejected.');
            // Only proofs: staff-entered payments were never submitted by the customer.
            if ((string) $model->payment_receipt_file !== '') {
                try {
                    $number = (string) Invoice::find()->select('invoice_number')->where(['id' => (int) $model->invoice_id])->scalar();
                    $userId = (int) Invoice::find()->select('user_id')->where(['id' => (int) $model->invoice_id])->scalar();
                    (new NotificationService())->create($userId, 'payment', 'Payment Proof Not Accepted', 'We could not verify your payment proof for invoice ' . $number . '. Please check the reference and submit it again, or contact support.', '/invoice/view?number=' . rawurlencode($number));
                    (new PaymentConfirmationService())->sendProofRejected($model, $reference);
                } catch (\Throwable $e) {
                    Yii::error(['message' => 'Could not notify the customer of a rejected proof.', 'transactionId' => $id, 'error' => $e->getMessage()], __METHOD__);
                    Yii::$app->session->addFlash('warning', 'The customer could not be emailed about this rejection. Please tell them directly.');
                }
            }
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Counts an unapplied payment (taken after its invoice was settled or closed) on a payable invoice of the
     * same customer, which may be its own once that is payable again. POST target_invoice = invoice number.
     */
    public function actionApply(int $id)
    {
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            // Lock order as approve and capture: transaction row, then invoice.
            Yii::$app->db->createCommand('SELECT id FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', $id)->queryScalar();
            $model = $this->findModel($id);
            if ($model->status !== Transaction::STATUS_CAPTURED || !$model->isUnapplied()) {
                throw new \DomainException('Only an unapplied captured payment can be applied to another invoice.');
            }
            // A refund made in the Razorpay dashboard must not be applied as if the money were still here.
            $razorpay = new RazorpayService();
            if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && $razorpay->isEnabled()) {
                $razorpay->assertRefundsRecorded($model);
            }
            $from = Invoice::findOne((int) $model->invoice_id);
            $number = trim(self::scalarParam($this->request->post('target_invoice', '')));
            $targetId = Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE invoice_number = :number AND user_id = :user FOR UPDATE')
                ->bindValues([':number' => $number, ':user' => (int) ($from->user_id ?? 0)])->queryScalar();
            $target = $targetId !== false ? Invoice::findOne((int) $targetId) : null;
            if ($target === null) {
                throw new \DomainException('Choose an invoice of the same customer.');
            }
            $paymentService = new InvoicePaymentService();
            $paymentService->assertPayable($target);
            $model->invoice_id = (int) $target->id;
            $model->is_unapplied = 0;
            $model->user_payment_notes = trim($model->user_payment_notes . "\nApplied to invoice " . $target->invoice_number . ' on ' . date('d M Y') . ' (paid on ' . ($from->invoice_number ?? '-') . ').');
            $model->updated_at = time();
            if (!$model->save(false, ['invoice_id', 'is_unapplied', 'user_payment_notes', 'updated_at'])) {
                throw new \RuntimeException('The payment could not be moved.');
            }
            (new PaymentProcessingService())->transactionChanged($model, false);
            $excess = $paymentService->getCapturedAmount((int) $target->id) - (float) $target->total_payable;
            $dbTransaction->commit();
            Yii::$app->session->setFlash(round($excess, 2) > 0 ? 'warning' : 'success', 'Payment applied to invoice ' . $target->invoice_number . '.'
                . (round($excess, 2) > 0 ? ' It now overpays that invoice by ₹' . number_format($excess, 2) . ': refund the difference.' : ''));
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Applying a payment failed.', 'transactionId' => $id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('error', $e instanceof \DomainException ? $e->getMessage() : 'The payment could not be applied.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Saved: the customer was told only that staff would refund or apply it.
        try {
            (new NotificationService())->create((int) $target->user_id, 'payment', 'Payment Confirmed', 'Your payment for invoice ' . $target->invoice_number . ' has been confirmed.', '/invoice/view?number=' . rawurlencode($target->invoice_number));
            (new PaymentConfirmationService())->send($model);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment was applied but the confirmation failed.', 'transactionId' => $id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->addFlash('warning', 'Payment applied to invoice ' . $target->invoice_number . ', but the confirmation email could not be sent.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Transactions are financial ledger records.
     *
     * Physical deletion is deliberately disabled.
     */
    public function actionDelete(int $id)
    {
        $model = $this->findModel($id);
        Yii::$app->session->setFlash('error', sprintf('Transaction #%d cannot be deleted. Financial transactions must remain in the payment history.', $model->id));

        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionReceipt(int $id)
    {
        $model = $this->findModel($id);
        if (empty($model->payment_receipt_file)) {
            throw new NotFoundHttpException('Payment receipt does not exist.');
        }

        $fileName = basename((string) $model->payment_receipt_file);
        $path = Yii::getAlias('@storage/payment-receipts/' . $fileName);
        if (!is_file($path) || !is_readable($path)) {
            throw new NotFoundHttpException('Payment receipt does not exist.');
        }
        return Yii::$app->response->sendFile($path, 'payment-receipt-' . $model->id . '.' . pathinfo($fileName, PATHINFO_EXTENSION), ['inline' => true]);
    }

    private function getInvoiceList(): array
    {
        $models = Invoice::find()
                ->where(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
        $list = [];
        foreach ($models as $invoice) {
            $list[(int) $invoice->id] = $invoice->invoice_number . ' - ' . $invoice->client_name . ' - ' . Yii::$app->formatter->asCurrency($invoice->total_payable, 'INR');
        }
        return $list;
    }

    /**
     * Save transaction and synchronise the parent invoice.
     *
     * Both operations are atomic.
     *
     * If invoice reconciliation or renewal fulfilment fails,
     * the transaction change is rolled back as well.
     *
     * @throws \Throwable
     */
    private function saveAndProcessPayment(Transaction $model): void
    {
        $dbTransaction = Yii::$app->db->beginTransaction();

        try {
            // An edit must not overwrite a payment approved or rejected meanwhile in another tab.
            if (!$model->getIsNewRecord()) {
                $status = Yii::$app->db->createCommand('SELECT status FROM {{%transaction}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $model->id)->queryScalar();
                if ($status !== Transaction::STATUS_PENDING) {
                    throw new \DomainException('This payment was ' . strtolower((string) $status) . ' meanwhile and can no longer be edited.');
                }
            }
            // Before the insert: its foreign key takes a shared lock on the invoice, which two saves can't both upgrade.
            if ($model->getIsNewRecord()) {
                Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $model->invoice_id)->queryScalar();
            }
            if (!$model->save()) {
                throw new \RuntimeException('Transaction could not be saved: ' . json_encode($model->errors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }

            /*
             * false means:
             *
             * Do not start another DB transaction inside
             * PaymentProcessingService because this method
             * already owns the transaction.
             */
            (new PaymentProcessingService())->transactionChanged($model, false);
            $dbTransaction->commit();
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Find transaction.
     */
    protected function findModel(int $id): Transaction
    {
        $model = Transaction::findOne(['id' => $id]);
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested transaction does not exist.');
    }
}
