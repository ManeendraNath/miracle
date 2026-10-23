<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\TransactionSearch;
use common\models\Transaction;
use common\services\billing\PaymentConfirmationService;
use common\services\billing\PaymentProcessingService;
use Yii;
use yii\web\NotFoundHttpException;

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
        $model = new Transaction();

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
            if ($paymentTransaction->status !== Transaction::STATUS_PENDING) {
                throw new \DomainException('Only pending payments can be approved.');
            }
            $now = time();
            $paymentTransaction->status = Transaction::STATUS_CAPTURED;
            $paymentTransaction->paid_at = $now;
            $paymentTransaction->updated_at = $now;
            if (!$paymentTransaction->save(false, ['status', 'paid_at', 'updated_at'])) {
                throw new \RuntimeException('Payment could not be approved.');
            }
            (new PaymentProcessingService())->transactionChanged($paymentTransaction, false);
            $dbTransaction->commit();
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Payment approval failed.', 'transactionId' => (int) $paymentTransaction->id, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('error', 'Payment could not be approved.');
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
            $model->status = Transaction::STATUS_FAILED;
            $model->updated_at = time();
            if (!$model->save(false, ['status', 'updated_at'])) {
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
            throw new NotFoundHttpException('Payment receipt does not exist.' . $path);
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
