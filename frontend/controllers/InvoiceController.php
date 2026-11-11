<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use common\components\LoginThrottle;
use common\models\Invoice;
use common\models\Transaction;
use common\models\forms\PaymentProofForm;
use common\services\admin\AdminAlertService;
use common\services\billing\InvoiceCouponService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\InvoicePdfService;
use common\services\payment\PayPalService;
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
                    'paypal-order' => ['POST'],
                    'paypal-capture' => ['POST'],
                    'apply-coupon' => ['POST'],
                    'remove-coupon' => ['POST'],
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

        $byStatus = Invoice::forCustomer()->select(['COUNT(*)', 'status'])->andWhere(['user_id' => $userId])->groupBy('status')->indexBy('status')->column();
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
            'query' => Invoice::forCustomer()
                ->andWhere(['user_id' => $userId])
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
        $pendingPayment = Transaction::find()->where(['invoice_id' => $model->id, 'status' => Transaction::STATUS_PENDING])->andWhere(\common\services\payment\RazorpayService::notUnpaidOrderCondition())->orderBy(['id' => SORT_DESC])->one();
        $paymentService = new InvoicePaymentService();
        $paypal = new PayPalService();
        return $this->render(
            'view',
            [
                            'model' => $model,
                            'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
                            'outstandingAmount' => $paymentService->getOutstanding($model),
                            'pendingPayment' => $pendingPayment,
                            'carriedInto' => $model->status === Invoice::STATUS_CARRIED_FORWARD ? $paymentService->openInvoiceCarrying((int) $model->id) : null,
                            'razorpayEnabled' => (new RazorpayService())->acceptsCustomerPayments($model->isExport()),
                            // Clients abroad: PayPal in US dollars.
                            'paypal' => $model->isExport() && $paypal->acceptsCustomerPayments() ? ['clientId' => $paypal->getClientId(), 'rate' => PayPalService::rateFor($model), 'until' => (float) $model->usd_rate_locked > 0 && (string) $model->usd_rate_until >= date('Y-m-d') ? $model->usd_rate_until : null] : null,
                            'canChangeCoupon' => (new InvoiceCouponService())->blockReason($model) === null,
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
        // An earlier attempt may have been paid with both the callback and the webhook lost: never charge twice.
        // Before any lock (see lockUnpaidOrders), and a failed lookup doesn't stop the customer paying.
        if ($razorpay->isEnabled() && $razorpay->recoverPaidOrders((int) $invoice->id) > 0) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'We have just found your earlier payment and recorded it. Please reload this page to see your balance.'];
        }
        if ($razorpay->paidButNotRecorded) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'We can see an earlier payment for this invoice that has not been recorded yet. Please do not pay again: try this page in a few minutes, or contact support.'];
        }
        // Lock the invoice so a coupon applied in another tab (InvoiceCouponService locks the same row)
        // can't change the total between reading the amount and creating the order.
        // ponytail: the row stays locked during the Razorpay API call (up to 20 s if it is slow).
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            RazorpayService::lockUnpaidOrders((int) $invoice->id); // order rows before the invoice, as coupon changes lock them
            Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', $invoice->id)->execute();
            $invoice->refresh();
            $outstanding = (new InvoicePaymentService())->getOutstanding($invoice);
            $payable = in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) && $outstanding > 0;
            $proofPending = (new InvoicePaymentService())->hasPendingProof((int) $invoice->id);
            if (!$razorpay->acceptsCustomerPayments($invoice->isExport()) || !$payable || $proofPending) {
                $dbTransaction->rollBack();
                Yii::$app->response->statusCode = 422;
                return ['error' => $proofPending ? 'A payment for this invoice is already awaiting verification.' : 'Online payment is not available for this invoice.'];
            }
            if ($outstanding < 1) {
                $dbTransaction->rollBack();
                Yii::$app->response->statusCode = 422;
                return ['error' => 'The remaining ₹' . number_format($outstanding, 2) . ' is below the ₹1 minimum for online payment. Please pay it by UPI or bank transfer, or contact support.'];
            }
            $transaction = $razorpay->createOrder($invoice, $outstanding);
            $dbTransaction->commit();
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Could not start Razorpay checkout.', 'invoice' => $invoice->invoice_number, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->response->statusCode = 502;
            return ['error' => 'Could not start online payment. Please try again shortly.'];
        }
        /** @var \common\models\User $user */
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
        // A crafted array reads as empty and fails the checks below.
        $field = static fn(string $name): string => is_string($request->post($name)) ? $request->post($name) : '';
        $orderId = $field('razorpay_order_id');
        $paymentId = $field('razorpay_payment_id');
        $signature = $field('razorpay_signature');
        $razorpay = new RazorpayService();

        $ownsOrder = $orderId !== '' && Transaction::find()->where(['gateway_order_id' => $orderId, 'invoice_id' => $invoice->id])->exists();
        if (!$ownsOrder || $paymentId === '' || !$razorpay->verifyCheckoutSignature($orderId, $paymentId, $signature)) {
            Yii::warning(['message' => 'Rejected Razorpay checkout callback.', 'invoice' => $invoice->invoice_number, 'orderId' => $orderId], __METHOD__);
            Yii::$app->session->setFlash('error', 'We could not verify this payment. If money was deducted, it will be confirmed automatically or contact support.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        try {
            // Unapplied: the invoice was already settled or closed (e.g. paid by UPI in the meantime).
            $captured = $razorpay->capture($orderId, $paymentId);
            if ($captured->status === Transaction::STATUS_REFUNDED) {
                Yii::$app->session->setFlash('warning', 'This payment has already been refunded. Reference: ' . $paymentId);
            } elseif ($captured->isUnapplied()) {
                Yii::$app->session->setFlash('warning', 'Payment received, but this invoice no longer needed it. Our team will refund it or apply it to your account. Reference: ' . $paymentId);
            } else {
                Yii::$app->session->setFlash('success', 'Payment received. Thank you!');
            }
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Razorpay checkout capture failed.', 'invoice' => $invoice->invoice_number, 'orderId' => $orderId, 'paymentId' => $paymentId, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->session->setFlash('warning', 'Your payment was received but could not be applied automatically. It will be confirmed shortly, so please do not pay again. Reference: ' . $paymentId);
            // Only a refusal: a lock timeout says nothing about the payment, and the webhook, sync or next Pay Online records it.
            if (!$e instanceof \DomainException) {
                return $this->redirect(['view', 'number' => $invoice->invoice_number]);
            }
            try {
                $amount = (float) Transaction::find()->select('amount')->where(['gateway_order_id' => $orderId])->scalar();
                AdminAlertService::paymentNotRecorded($orderId, $paymentId, $amount, $e->getMessage());
            } catch (\Throwable $alertError) {
                Yii::error(['message' => 'Could not alert admins about an unrecorded payment.', 'paymentId' => $paymentId, 'error' => $alertError->getMessage()], __METHOD__);
            }
        }
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
    }

    /** Creates a PayPal order (in USD) for the outstanding amount; the PayPal button opens it. */
    public function actionPaypalOrder(string $number): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $invoice = $this->findOwnedInvoice($number);
        $paypal = new PayPalService();
        // An earlier order may have been paid with the browser lost: never charge twice.
        if ($paypal->isEnabled() && $paypal->recoverPaidOrders((int) $invoice->id) > 0) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'We have just found your earlier payment and recorded it. Please reload this page to see your balance.'];
        }
        if ($paypal->paidButNotRecorded) {
            Yii::$app->response->statusCode = 422;
            return ['error' => 'We can see an earlier payment for this invoice that has not been recorded yet. Please do not pay again: try this page in a few minutes, or contact support.'];
        }
        // Before any lock: the rate may need fetching (a few seconds at most, twice a day).
        PayPalService::usdRate();
        // Locked as in actionPayOnline: a coupon can't change the total between reading it and creating the order.
        // ponytail: the invoice stays locked during the PayPal API calls (up to 20 s each if PayPal is slow).
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            RazorpayService::lockUnpaidOrders((int) $invoice->id); // order rows before the invoice, as coupon changes lock them
            Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', $invoice->id)->execute();
            $invoice->refresh();
            $paymentService = new InvoicePaymentService();
            $outstanding = $paymentService->getOutstanding($invoice);
            $payable = in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) && $outstanding > 0;
            $proofPending = $paymentService->hasPendingProof((int) $invoice->id);
            if (!$invoice->isExport() || !$paypal->acceptsCustomerPayments() || !$payable || $proofPending) {
                $dbTransaction->rollBack();
                Yii::$app->response->statusCode = 422;
                return ['error' => $proofPending ? 'A payment for this invoice is already awaiting verification.' : 'PayPal is not available for this invoice.'];
            }
            $transaction = $paypal->createOrder($invoice, $outstanding);
            $dbTransaction->commit();
        } catch (\DomainException $e) {
            $dbTransaction->rollBack();
            Yii::$app->response->statusCode = 422;
            return ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error(['message' => 'Could not start PayPal checkout.', 'invoice' => $invoice->invoice_number, 'error' => $e->getMessage()], __METHOD__);
            Yii::$app->response->statusCode = 502;
            return ['error' => 'Could not start PayPal payment. Please try again shortly.'];
        }

        return ['id' => $transaction->gateway_order_id];
    }

    /** The customer approved the order in PayPal: capture it and record the payment. The page reloads after. */
    public function actionPaypalCapture(string $number): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $invoice = $this->findOwnedInvoice($number);
        $orderId = Yii::$app->request->post('order_id');
        $orderId = is_string($orderId) ? $orderId : '';
        $session = Yii::$app->session;
        if ($orderId === '' || !Transaction::find()->where(['gateway_order_id' => $orderId, 'invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_PAYPAL])->exists()) {
            $session->setFlash('error', 'We could not find this PayPal payment. If money was taken, contact support.');
            return ['ok' => false];
        }
        try {
            $captured = (new PayPalService())->captureOrder($orderId);
            if ($captured->status === Transaction::STATUS_FAILED && $captured->user_payment_notes === \common\services\payment\RazorpayService::CLOSED_NOTE) {
                $session->setFlash('warning', 'This payment had expired, so PayPal did not take any money. Please reload the page and pay again.');
            } elseif ($captured->status === Transaction::STATUS_FAILED) {
                $session->setFlash('warning', 'PayPal did not take this payment. ' . ucfirst((string) preg_replace('/^Not taken: /', '', (string) $captured->user_payment_notes)) . ' Please reload the page and check the amount due.');
            } elseif ($captured->status === Transaction::STATUS_PENDING) {
                $session->setFlash('warning', 'PayPal is still processing your payment. We will confirm it once PayPal completes it, so please do not pay again.');
            } elseif ($captured->isUnapplied()) {
                $session->setFlash('warning', 'Payment received, but this invoice no longer needed it. Our team will refund it or apply it to your account. Reference: ' . $captured->gateway_payment_id);
            } else {
                $session->setFlash('success', 'Payment received. Thank you!');
            }
        } catch (\Throwable $e) {
            Yii::error(['message' => 'PayPal capture failed.', 'invoice' => $invoice->invoice_number, 'orderId' => $orderId, 'error' => $e->getMessage()], __METHOD__);
            $session->setFlash('warning', $e instanceof \DomainException
                ? 'PayPal took a payment we could not match to this invoice. Our team will check it and contact you, so please do not pay again. Reference: ' . $orderId
                : 'We could not confirm your PayPal payment yet. If PayPal shows it as paid, it will be recorded shortly, so please do not pay again. Reference: ' . $orderId);
            if ($e instanceof \DomainException) {
                try {
                    $row = Transaction::findOne(['gateway_order_id' => $orderId]);
                    $usd = json_decode((string) $row?->raw_payload, true)['usd'] ?? null;
                    AdminAlertService::paymentNotRecorded($orderId, (string) ($row?->gateway_payment_id ?: $orderId), (float) $row?->amount, $e->getMessage() . ($usd !== null ? ' (US$' . $usd . ')' : ''), 'PayPal');
                } catch (\Throwable $alertError) {
                    Yii::error(['message' => 'Could not alert admins about an unrecorded PayPal payment.', 'orderId' => $orderId, 'error' => $alertError->getMessage()], __METHOD__);
                }
            }
        }

        return ['ok' => true];
    }

    public function actionApplyCoupon(string $number)
    {
        $invoice = $this->findOwnedInvoice($number);
        $code = Yii::$app->request->post('coupon_code');
        // Five wrong codes and a wait, so codes can't be guessed; the same answer whether a code exists or is someone else's.
        // A code that works doesn't reset it: apply, remove and guess again would never lock.
        $throttleName = 'user#' . (int) Yii::$app->user->id;
        if (LoginThrottle::isLocked('coupon', $throttleName)) {
            Yii::$app->session->setFlash('error', 'Too many attempts. Please try again in 15 minutes.');
            return $this->redirect(['view', 'number' => $invoice->invoice_number]);
        }
        try {
            (new InvoiceCouponService())->apply($invoice, is_string($code) ? $code : '');
            Yii::$app->session->setFlash('success', 'Coupon applied.');
        } catch (\DomainException $e) {
            $unknown = in_array($e->getMessage(), ['Coupon code is invalid.', 'This coupon is not assigned to your account.'], true);
            if ($unknown) {
                LoginThrottle::fail('coupon', $throttleName);
            }
            Yii::$app->session->setFlash('error', $unknown ? 'This coupon code cannot be used on this invoice.' : $e->getMessage());
        }
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
    }

    public function actionRemoveCoupon(string $number)
    {
        $invoice = $this->findOwnedInvoice($number);
        try {
            (new InvoiceCouponService())->remove($invoice);
            Yii::$app->session->setFlash('success', 'Coupon removed.');
        } catch (\DomainException $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
    }

    private function findOwnedInvoice(string $number): Invoice
    {
        $model = Invoice::forCustomer()->with(['items'])->andWhere(['invoice_number' => $number, 'user_id' => Yii::$app->user->id])->one();
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
        $pendingExists = (new InvoicePaymentService())->hasPendingProof((int) $invoice->id);
        if ($pendingExists) {
            Yii::$app->session->setFlash('warning', 'A payment for this invoice is already awaiting verification.');
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
            $pendingExists = (new InvoicePaymentService())->hasPendingProof((int) $invoice->id);
            if ($pendingExists) {
                $dbTransaction->rollBack();
                Yii::$app->session->setFlash('warning', 'A payment for this invoice is already awaiting verification.');
                return $this->redirect(['view', 'number' => $invoice->invoice_number]);
            }
            // Re-read under the lock: an online payment may have settled it since the form was checked.
            $invoice->refresh();
            $outstanding = (new InvoicePaymentService())->getOutstanding($invoice);
            if (!in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) || $outstanding <= 0) {
                $dbTransaction->rollBack();
                Yii::$app->session->setFlash('warning', 'This invoice has no outstanding balance any more.');
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
