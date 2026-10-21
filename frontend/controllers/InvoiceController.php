<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use common\models\Invoice;
use common\models\Transaction;
use common\models\forms\PaymentProofForm;
use common\services\billing\InvoicePaymentService;
use common\services\billing\InvoicePdfService;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

final class InvoiceController extends Controller
{

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = Invoice::find()
                ->where([
                    'user_id' =>
                    Yii::$app->user->id,
                ])
                ->orderBy([
                    'created_at' => SORT_DESC,
                    'id' => SORT_DESC,
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render(
                        'index',
                        [
                            'dataProvider' =>
                            $dataProvider,
                        ]
                );
    }

    public function actionView(string $number)
    {
        $model = $this->findOwnedInvoice($number);
        $pendingPayment = Transaction::find()->where(['invoice_id' => $model->id, 'gateway_id' => 2, 'status' => Transaction::STATUS_PENDING])->orderBy(['id' => SORT_DESC])->one();
        $paymentService = new InvoicePaymentService();
        return $this->render(
                        'view',
                        [
                            'model' => $model,
                            'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
                            'outstandingAmount' => $paymentService->getOutstanding($model),
                            'pendingPayment' => $pendingPayment,
                        ]
                );
    }

    private function findOwnedInvoice(string $number): Invoice
    {
        $model = Invoice::find()->with(['items'])->where(['invoice_number' => $number, 'user_id' => Yii::$app->user->id])->one();
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('Invoice does not exist.');
    }

    public function actionDownload(string $number)
    {
        $invoice = $this->findOwnedInvoice($number);
        $pdf = (new InvoicePdfService())->render($invoice);
        return Yii::$app->response->sendContentAsFile($pdf, 'Invoice_' . $invoice->invoice_number . '.pdf', ['mimeType' => 'application/pdf', 'inline' => false]);
    }

    public function actionSubmitPayment(string $number)
    {
        $invoice = $this->findOwnedInvoice($number);
        if (!in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
            Yii::$app->session->setFlash('warning', 'This invoice is not currently payable.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        $paymentService = new InvoicePaymentService();
        $outstanding = $paymentService->getOutstanding($invoice);
        if ($outstanding <= 0) {
            Yii::$app->session->setFlash('warning', 'This invoice has no outstanding balance.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        $pendingExists = Transaction::find()->where(['invoice_id' => $invoice->id, 'gateway_id' => 2, 'status' => Transaction::STATUS_PENDING])->exists();
        if ($pendingExists) {
            Yii::$app->session->setFlash('warning', 'A payment proof for this invoice is already awaiting verification.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        $model = new PaymentProofForm();
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->receiptFile = UploadedFile::getInstance($model, 'receiptFile');
            if ($model->validate()) {
                return $this->savePaymentProof($invoice, $model, $outstanding);
            }
        }
        return $this->render('submit-payment', ['model' => $model, 'invoice' => $invoice, 'outstandingAmount' => $outstanding]);
    }

    private function savePaymentProof(Invoice $invoice, PaymentProofForm $form, float $outstanding)
    {
        $existing = Transaction::find()->where(['gateway_payment_id' => $form->gateway_payment_id])->exists();
        if ($existing) {
            $form->addError('gateway_payment_id', 'This payment reference has already been submitted.');
            return $this->render('submit-payment', ['model' => $form, 'invoice' => $invoice, 'outstandingAmount' => $outstanding]);
        }
        $pendingExists = Transaction::find()->where(['invoice_id' => $invoice->id, 'gateway_id' => 2, 'status' => Transaction::STATUS_PENDING])->exists();
        if ($pendingExists) {
            Yii::$app->session->setFlash('warning', 'A payment proof for this invoice is already awaiting verification.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        $extension = strtolower($form->receiptFile->extension);
        $fileName = bin2hex(random_bytes(24)) . '.' . $extension;
        $directory = Yii::getAlias('@common/runtime/payment-receipts');
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)
        ) {
            throw new \RuntimeException('Payment receipt storage is unavailable.');
        }
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $fileName;
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            if (!$form->receiptFile->saveAs($absolutePath)) {
                throw new \RuntimeException('Payment receipt could not be stored.');
            }
            $transaction = new Transaction();
            $transaction->invoice_id = (int) $invoice->id;
            $transaction->gateway_id = 2;
            $transaction->gateway_payment_id = $form->gateway_payment_id;
            $transaction->gateway_order_id = null;
            $transaction->amount = round($outstanding, 2);
            $transaction->gateway_fee = 0.00;
            $transaction->status = Transaction::STATUS_PENDING;
            $transaction->user_payment_notes = $form->user_payment_notes !== '' ? $form->user_payment_notes : null;
            $transaction->payment_receipt_file = $fileName;
            $transaction->paid_at = null;
            $transaction->created_at = time();
            $transaction->updated_at = time();
            if (!$transaction->save()) {
                throw new \RuntimeException('Payment submission could not be recorded: ' . json_encode($transaction->errors, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            }
            $dbTransaction->commit();
            Yii::$app->session->setFlash('success', 'Payment proof submitted successfully. ' . 'The payment is pending verification.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
            Yii::error(['message' => 'Customer payment proof submission failed.', 'invoiceId' => (int) $invoice->id, 'userId' => (int) Yii::$app->user->id, 'error' => $e->getMessage()], __METHOD__);
            $form->addError('receiptFile', 'Payment proof could not be submitted. Please try again.');
            return $this->render('submit-payment', ['model' => $form, 'invoice' => $invoice, 'outstandingAmount' => $outstanding]);
        }
    }
}
