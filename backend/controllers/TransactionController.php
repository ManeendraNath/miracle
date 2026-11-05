<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\TransactionSearch;
use common\models\Invoice;
use common\models\Transaction;
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
        if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY) {
            Yii::$app->session->setFlash('error', 'Razorpay payments are managed by the gateway and cannot be edited.');
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
            if ((int) $paymentTransaction->gateway_id === Transaction::GATEWAY_RAZORPAY) {
                throw new \DomainException('Razorpay payments are confirmed automatically and cannot be approved manually.');
            }
            // E.g. paid online meanwhile: approving would collect the money twice.
            $lockedInvoiceId = Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $paymentTransaction->invoice_id)->queryScalar();
            $invoice = $lockedInvoiceId !== false ? Invoice::findOne((int) $lockedInvoiceId) : null;
            if ($invoice === null) {
                throw new \DomainException('This payment has no invoice.');
            }
            (new InvoicePaymentService())->assertPayable($invoice);
            $now = time();
            $paymentTransaction->status = Transaction::STATUS_CAPTURED;
            $paymentTransaction->paid_at = $now;
            $paymentTransaction->updated_at = $now;
            if (!$paymentTransaction->save(false, ['status', 'paid_at', 'updated_at'])) {
                throw new \RuntimeException('Payment could not be approved.');
            }
            (new PaymentProcessingService())->transactionChanged($paymentTransaction, false);
            $excess = (new InvoicePaymentService())->getCapturedAmount((int) $invoice->id) - (float) $invoice->total_payable;
            $dbTransaction->commit();
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
            (new PaymentConfirmationService())->send($paymentTransaction);
            (new NotificationService())->create((int) $paymentTransaction->invoice->user_id, 'payment', 'Payment Confirmed', 'Your payment for invoice ' . $paymentTransaction->invoice->invoice_number . ' has been confirmed.', '/invoice/view?number=' . rawurlencode($paymentTransaction->invoice->invoice_number));
            Yii::$app->session->setFlash('success', 'Payment approved and confirmation email sent.');
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment was approved but confirmation email failed.', 'transactionId' => (int) $paymentTransaction->id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('warning', 'Payment approved successfully, but the confirmation email could not be sent.');
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
            $input = trim((string) $this->request->post('refund_amount', ''));
            $amount = $input === '' ? $remaining : round((float) $input, 2);
            if (($input !== '' && !is_numeric($input)) || $amount <= 0 || $amount > $remaining) {
                throw new \DomainException('Enter a refund amount between ₹0.01 and ₹' . number_format($remaining, 2) . '.');
            }
            $full = abs($amount - $remaining) < 0.005;
            // The form carries what was refunded when it was shown: a second tab or a double click with the
            // same partial amount would otherwise pass every check again and send it twice.
            $expected = $this->request->post('refunded_before');
            if ($expected !== null && abs((float) $expected - (float) $model->refunded_amount) >= 0.005) {
                throw new \DomainException('This payment was refunded meanwhile (now ₹' . number_format((float) $model->refunded_amount, 2) . '). Check the amount and try again.');
            }

            // Its unpaid balance already moved to a newer invoice as a fixed arrears line; refunding it here
            // would leave that line short. An unapplied payment was never part of the arrears, so it can go back.
            // ponytail: refused outright; raise the arrears on the newer invoice if this case gets common.
            if (($invoice->status ?? null) === Invoice::STATUS_CARRIED_FORWARD && !$model->isUnapplied()) {
                $target = $invoice->invoiceItem->invoice ?? null; // invoiceItem = the arrears line that carried it
                throw new \DomainException('This invoice was carried forward' . ($target !== null ? ' into ' . $target->invoice_number : '') . ', so its payments cannot be refunded here.');
            }
            $paymentService = new InvoicePaymentService();
            $canReconcile = $invoice !== null && !in_array($invoice->status, InvoicePaymentService::CLOSED_STATUSES, true);
            // Closing after a partial refund keeps the rest by agreement (e.g. a domain fee): nothing more is owed.
            if ($close) {
                $blocker = $canReconcile ? $paymentService->closeBlocker($invoice, (int) $model->id) : null;
                if ($blocker !== null) {
                    throw new \DomainException($blocker);
                }
            }

            $note = 'Refunded ₹' . number_format($amount, 2) . ' on ' . date('d M Y') . '.';
            $razorpay = new RazorpayService();
            if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && $razorpay->isEnabled()) {
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

            $flash = 'success';
            $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded. The invoice was ' . (Invoice::getStatuses()[$invoice->status ?? ''] ?? $invoice->status ?? '-') . ', so it is unchanged.';
            if ($canReconcile) {
                (new PaymentProcessingService())->transactionChanged($model, false);
                $invoice->refresh();
                if ($close) {
                    $released = $paymentService->closeAsRefunded($invoice, (int) $model->id);
                    $message = 'Refund of ₹' . number_format($amount, 2) . ' recorded and the invoice closed: nothing more is owed.' . ($full ? '' : ' The rest of the payment is kept.');
                    if ($released > 0) {
                        $flash = 'warning';
                        $message .= ' Its unpaid renewal is billed again in full on the next run' . ($full ? '' : ', with no credit for the money kept,') . ' unless the service is disabled.';
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
            if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY) {
                throw new \DomainException('Razorpay orders are settled automatically and cannot be rejected manually.');
            }
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
            $from = Invoice::findOne((int) $model->invoice_id);
            $number = trim((string) $this->request->post('target_invoice', ''));
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
            (new PaymentConfirmationService())->send($model);
            (new NotificationService())->create((int) $target->user_id, 'payment', 'Payment Confirmed', 'Your payment for invoice ' . $target->invoice_number . ' has been confirmed.', '/invoice/view?number=' . rawurlencode($target->invoice_number));
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment was applied but the confirmation failed.', 'transactionId' => $id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('warning', 'Payment applied to invoice ' . $target->invoice_number . ', but the confirmation email could not be sent.');
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
