<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use common\models\Invoice;
use common\models\Transaction;
use common\models\forms\PaymentProofForm;
use common\services\admin\AdminAlertService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\InvoicePdfService;
use common\services\payment\RazorpayService;
use common\services\notification\NotificationService;
use common\services\notification\WhatsAppService;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

final class InvoiceController extends Controller
{
    public $layout = 'account';

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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'pay-online' => ['POST'],
                    'razorpay-callback' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(string $filter = 'all')
    {
        $filters = [
            'all' => null,
            'due' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID],
            'paid' => [Invoice::STATUS_PAID],
        ];
        $filter = array_key_exists($filter, $filters) ? $filter : 'all';
        $userId = (int) Yii::$app->user->id;

        $byStatus = Invoice::find()->select(['COUNT(*)', 'status'])->where(['user_id' => $userId])->groupBy('status')->indexBy('status')->column();
        $counts = [];
        foreach ($filters as $key => $statuses) {
            $counts[$key] = $statuses === null ? array_sum($byStatus) : array_sum(array_intersect_key($byStatus, array_flip($statuses)));
        }

        $paymentService = new InvoicePaymentService();
        $outstandingTotal = 0.0;
        foreach (Invoice::find()->where(['user_id' => $userId, 'status' => $filters['due']])->all() as $due) {
            $outstandingTotal += $paymentService->getOutstanding($due);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => Invoice::find()
                ->where(['user_id' => $userId])
                ->andFilterWhere(['status' => $filters[$filter]])
                ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'filter' => $filter,
            'counts' => $counts,
            'outstandingTotal' => round($outstandingTotal, 2),
            'paymentService' => $paymentService,
        ]);
    }

    public function actionView(string $number)
    {
        $model = $this->findOwnedInvoice($number);
        $pendingPayment = Transaction::find()->where(['invoice_id' => $model->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'status' => Transaction::STATUS_PENDING])->orderBy(['id' => SORT_DESC])->one();
        $paymentService = new InvoicePaymentService();
        return $this->render(
            'view',
            [
                            'model' => $model,
                            'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
                            'outstandingAmount' => $paymentService->getOutstanding($model),
                            'pendingPayment' => $pendingPayment,
                            'razorpayEnabled' => (new RazorpayService())->isEnabled(),
                        ]
        );
    }

    /**
     * Creates (or reuses) a Razorpay order for the outstanding amount and returns
     * the Checkout options for the browser.
     */
    public function actionPayOnline(string $number): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $invoice = $this->findOwnedInvoice($number);
        $razorpay = new RazorpayService();
        $outstanding = (new InvoicePaymentService())->getOutstanding($invoice);
        $payable = in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) && $outstanding > 0;
        $proofPending = Transaction::find()->where(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'status' => Transaction::STATUS_PENDING])->exists();
        if (!$razorpay->isEnabled() || !$payable || $proofPending) {
            Yii::$app->response->statusCode = 422;
            return ['error' => $proofPending ? 'A payment proof for this invoice is already awaiting verification.' : 'Online payment is not available for this invoice.'];
        }
        try {
            $transaction = $razorpay->createOrder($invoice, $outstanding);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Could not start Razorpay checkout.', 'invoice' => $invoice->invoice_number, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->response->statusCode = 502;
            return ['error' => 'Could not start online payment. Please try again shortly.'];
        }
        $user = Yii::$app->user->identity;
        return [
            'key' => $razorpay->getKeyId(),
            'order_id' => $transaction->gateway_order_id,
            'amount' => (int) round((float) $transaction->amount * 100),
            'currency' => 'INR',
            'name' => Yii::$app->name,
            'description' => 'Invoice ' . $invoice->invoice_number,
            'prefill' => [
                'name' => (string) $invoice->client_name,
                'email' => (string) $user->email,
                'contact' => (string) ($user->mobile ?? ''),
            ],
        ];
    }

    /**
     * Browser return from Razorpay Checkout. The signature proves the payment
     * belongs to our order; the webhook captures it too if this request is lost.
     */
    public function actionRazorpayCallback(string $number)
    {
        $invoice = $this->findOwnedInvoice($number);
        $request = Yii::$app->request;
        $orderId = (string) $request->post('razorpay_order_id', '');
        $paymentId = (string) $request->post('razorpay_payment_id', '');
        $signature = (string) $request->post('razorpay_signature', '');
        $razorpay = new RazorpayService();

        $ownsOrder = $orderId !== '' && Transaction::find()->where(['gateway_order_id' => $orderId, 'invoice_id' => $invoice->id])->exists();
        if (!$ownsOrder || $paymentId === '' || !$razorpay->verifyCheckoutSignature($orderId, $paymentId, $signature)) {
            Yii::warning(['message' => 'Rejected Razorpay checkout callback.', 'invoice' => $invoice->invoice_number, 'orderId' => $orderId], __METHOD__);
            Yii::$app->session->setFlash('error', 'We could not verify this payment. If money was deducted, it will be confirmed automatically or contact support.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        try {
            $razorpay->capture($orderId, $paymentId);
            $invoice->refresh();
            if (in_array($invoice->status, [Invoice::STATUS_CANCELLED, Invoice::STATUS_CARRIED_FORWARD], true)) {
                Yii::$app->session->setFlash('warning', 'Payment received, but this invoice is no longer payable. Our team will refund it or apply it to your account. Reference: ' . $paymentId);
            } else {
                Yii::$app->session->setFlash('success', 'Payment received. Thank you!');
            }
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Razorpay checkout capture failed.', 'invoice' => $invoice->invoice_number, 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('warning', 'Your payment was received but could not be applied automatically. Our team has been notified and will confirm it shortly. Reference: ' . $paymentId);
            AdminAlertService::paymentSubmitted($invoice->invoice_number, (string) $invoice->client_name, (float) $invoice->total_payable);
        }
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
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
        $pendingExists = Transaction::find()->where(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'status' => Transaction::STATUS_PENDING])->exists();
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
        if ($form->receiptFile === null) {
            $form->addError('receiptFile', 'Please upload the payment receipt.');
            return $this->render('submit-payment', ['model' => $form, 'invoice' => $invoice, 'outstandingAmount' => $outstanding]);
        }
        if ($form->receiptFile !== null && $form->receiptFile->error !== UPLOAD_ERR_OK) {
            Yii::warning([
                'uploadError' => $form->receiptFile->error,
                'fileName' => $form->receiptFile->name,
            ]);
        }
        $extension = strtolower($form->receiptFile->extension);
        $fileName = bin2hex(random_bytes(24)) . '.' . $extension;
        $directory = Yii::getAlias('@storage/payment-receipts');
        if (
            !is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)
        ) {
            throw new \RuntimeException('Payment receipt storage is unavailable.');
        }
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $fileName;
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            // Lock the invoice so two concurrent submissions can't both pass the pending check.
            Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE', [':id' => (int) $invoice->id])->queryScalar();
            $pendingExists = Transaction::find()->where(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'status' => Transaction::STATUS_PENDING])->exists();
            if ($pendingExists) {
                $dbTransaction->rollBack();
                Yii::$app->session->setFlash('warning', 'A payment proof for this invoice is already awaiting verification.');
                return $this->redirect(['view', 'number' => $invoice->invoice_number]);
            }
            if (!$form->receiptFile->saveAs($absolutePath)) {
                throw new \RuntimeException('Payment receipt could not be stored.');
            }
            $transaction = new Transaction();
            $transaction->invoice_id = (int) $invoice->id;
            $transaction->gateway_id = Transaction::GATEWAY_UPI_BANK;
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
        // Committed: notification failures must not undo the submission.
        try {
            (new NotificationService())->create((int) $invoice->user_id, 'payment', 'Payment Proof Submitted', 'Your payment proof for invoice ' . $invoice->invoice_number . ' is awaiting verification.', '/invoice/view?number=' . rawurlencode($invoice->invoice_number));
            AdminAlertService::paymentSubmitted($invoice->invoice_number, $invoice->user->username, $transaction->amount);
            $components = [
                'body_1' => [
                    'type' => 'text',
                    'value' => $invoice->user->username,
                ],
                'body_2' => [
                    'type' => 'text',
                    'value' => $transaction->id,
                ],
            ];
            WhatsAppService::sendTemplate(Yii::$app->params['whatsapp']['adminMobile'], 'payment_submitted', $components, 'payment-submitted-' . $transaction->id);
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Payment proof saved but notifications failed.', 'transactionId' => (int) $transaction->id, 'error' => $e->getMessage()], __METHOD__);
        }
        Yii::$app->session->setFlash('success', 'Payment proof submitted successfully. ' . 'The payment is pending verification.');
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
    }
}
