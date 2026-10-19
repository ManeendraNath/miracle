<?php

declare(strict_types=1);

namespace backend\controllers;

use Yii;
use backend\models\searches\InvoiceSearch;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\services\billing\InvoiceCancellationService;
use common\services\billing\InvoiceDeliveryService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\ManualInvoiceService;
use common\models\forms\ManualInvoiceItemForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;

class InvoiceController extends BaseController
{

    /**
     * List invoices.
     */
    public function actionIndex()
    {
        $searchModel = new InvoiceSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Display invoice by database ID or invoice number.
     */
    public function actionView(string $number)
    {
        $model = is_numeric($number) ? Invoice::findOne((int) $number) : Invoice::findOne(['invoice_number' => $number]);

        if ($model === null) {
            throw new NotFoundHttpException('The specified invoice could not be located.');
        }

        $model->checkClientAccess();
        $paymentService = new InvoicePaymentService();
        $totals = $this->calculateDisplayTotals($model);

        return $this->render(
                        'view',
                        array_merge(
                                [
                                    'model' => $model,
                                    'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
                                    'outstandingAmount' => $paymentService->getOutstanding($model),
                                ],
                                $totals
                        )
                );
    }

    /**
     * Create a MANUAL invoice.
     *
     * Automatic renewal invoices are created by
     * AutoBillingService and do not use this action.
     */
    public function actionCreate()
    {
        $model = new Invoice();

        $model->discount_amount = 0.00;
        $items = [new ManualInvoiceItemForm()];
        if ($this->request->isPost) {
            $post = $this->request->post();
            $model->load($post);
            $items = $this->loadManualItems($post);
            try {
                $invoice = (new ManualInvoiceService())->create($model, $items);
                $client = User::findOne((int) $model->user_id);
                if ($client !== null) {
                    try {
                        (new InvoiceDeliveryService())->send($invoice);
                        Yii::$app->session->setFlash('success', 'Invoice created and emailed successfully.');
                    } catch (\Throwable $e) {
                        Yii::error($e, __METHOD__);
                        Yii::$app->session->setFlash('warning', 'Invoice created successfully, ' . 'but email delivery failed.');
                    }
                }
                return $this->redirect(['view', 'number' => $model->invoice_number]);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->render('create', ['model' => $model, 'items' => $items, 'usersList' => $this->getUsersList()]);
    }

    private function loadManualItems(array $post): array
    {
        $rows = $post['ManualInvoiceItemForm'] ?? [];
        $items = [];
        foreach ($rows as $row) {
            $item = new ManualInvoiceItemForm();
            $item->load([$item->formName() => $row]);
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Update invoice.
     *
     * Financial lifecycle status is deliberately protected.
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);
        /*
         * Only unpaid invoices may be edited.
         */
        if ($model->status !== Invoice::STATUS_UNPAID) {
            Yii::$app->session->setFlash('error', 'Only unpaid invoices can be edited.');
            return $this->redirect(['view', 'number' => $model->invoice_number]);
        }
        /*
         * Even if invoice status somehow became inconsistent,
         * never allow modification after money has been captured.
         */
        $paymentService = new InvoicePaymentService();
        if ($paymentService->getCapturedAmount((int) $model->id) > 0) {
            Yii::$app->session->setFlash('error', 'An invoice with captured payments cannot be edited.');
            return $this->redirect(['view', 'number' => $model->invoice_number]);
        }
        /*
         * Automatic renewal items and carried-forward balances
         * must never enter the manual invoice editor.
         */
        $containsSystemItems = InvoiceItem::find()
                ->where(['invoice_id' => $model->id])
                ->andWhere([
                    'or',
                    ['not', ['billing_event_id' => null]],
                    ['not', ['source_invoice_id' => null]],
                ])
                ->exists();
        if ($containsSystemItems) {
            Yii::$app->session->setFlash('error', 'Invoices containing renewal or carried-forward items cannot be edited manually.');
            return $this->redirect(['view', 'number' => $model->invoice_number]);
        }

        /*
         * Load only manual invoice items.
         */
        $itemModels = InvoiceItem::find()
                ->where([
                    'invoice_id' => $model->id,
                    'item_type' => InvoiceItem::TYPE_CUSTOM,
                ])
                ->orderBy(['id' => SORT_ASC])
                ->all();
        $items = [];
        foreach ($itemModels as $dbItem) {
            $form = new ManualInvoiceItemForm();
            $form->id = (int) $dbItem->id;
            $form->category = (string) $dbItem->category;
            $form->description = (string) $dbItem->description;
            $form->quantity = (int) $dbItem->quantity;
            $form->unit_price = (float) $dbItem->unit_price;
            $items[] = $form;
        }
        if (!$items) {
            $items[] = new ManualInvoiceItemForm();
        }
        if ($this->request->isPost) {
            $post = $this->request->post();
            /*
             * These fields must never be controlled by the
             * standard manual invoice update form.
             */
            $immutableAttributes = [
                'user_id' => $model->user_id,
                'invoice_number' => $model->invoice_number,
                'status' => $model->status,
                'billing_run_id' => $model->billing_run_id,
                'client_name' => $model->client_name,
                'client_address_line_1' => $model->client_address_line_1,
                'client_address_line_2' => $model->client_address_line_2,
                'client_city' => $model->client_city,
                'client_state' => $model->client_state,
                'client_state_code' => $model->client_state_code,
                'client_postal_code' => $model->client_postal_code,
                'client_gstin' => $model->client_gstin,
                'client_pan' => $model->client_pan,
                'place_of_supply' => $model->place_of_supply,
                'cgst_percent' => $model->cgst_percent,
                'sgst_percent' => $model->sgst_percent,
                'igst_percent' => $model->igst_percent,
                'subtotal_amount' => $model->subtotal_amount,
                'total_payable' => $model->total_payable,
            ];
            $model->load($post);
            foreach ($immutableAttributes as $attribute => $value) {
                $model->$attribute = $value;
            }
            $items = $this->loadManualItems($post);
            try {
                (new ManualInvoiceService())->update($model, $items);
                Yii::$app->session->setFlash('success', 'Invoice updated successfully.');
                return $this->redirect(['view', 'number' => $model->invoice_number]);
            } catch (\Throwable $e) {
                Yii::error($e, __METHOD__);
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        return $this->render('update', ['model' => $model, 'items' => $items]);
    }

    public function actionResendInvoice(int $id)
    {
        $invoice = $this->findModel($id);
        try {
            (new InvoiceDeliveryService())->send($invoice, true);
            Yii::$app->session->setFlash('success', 'Invoice email sent successfully.');
        } catch (\Throwable $e) {
            Yii::error(
                    [
                        'message' => 'Invoice email delivery failed.',
                        'invoiceId' => (int) $invoice->id,
                        'invoiceNumber' => $invoice->invoice_number,
                        'error' => $e->getMessage(),
                    ],
                    __METHOD__
            );
            Yii::$app->session->setFlash('error', 'Invoice email could not be sent. The invoice itself was not changed.');
        }
        return $this->redirect(['view', 'number' => $invoice->invoice_number]);
    }

    /**
     * Cancel invoice.
     *
     * We DO NOT delete financial records.
     */
    public function actionCancel(int $id)
    {
        $model = $this->findModel($id);
        try {
            (new InvoiceCancellationService())->cancel($model, sprintf('Cancelled manually by admin user ID %s.', Yii::$app->user->id));
            Yii::$app->session->setFlash('success', 'Invoice cancelled successfully.');
        } catch (\Throwable $e) {
            Yii::error($e, __METHOD__);
            Yii::$app->session->setFlash('error', $e->getMessage());
        }
        return $this->redirect(['view', 'number' => $model->invoice_number]);
    }

    /**
     * Never physically delete an invoice.
     */
    public function actionDelete(int $id)
    {
        $model = $this->findModel($id);
        Yii::$app->session->setFlash('error', sprintf('Invoice %s cannot be deleted. Use Cancel Invoice instead.', $model->invoice_number));
        return $this->redirect(['view', 'number' => $model->invoice_number]);
    }

    protected function findModel(int $id): Invoice
    {
        $model = Invoice::findOne(['id' => $id]);
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested invoice does not exist.');
    }

    private function getUsersList(): array
    {
        return ArrayHelper::map(
                        User::find()->orderBy(['username' => SORT_ASC])->asArray()->all(),
                        'id',
                        static function (array $user): string {
                            return sprintf('%s (%s)', $user['username'], $user['email']);
                        }
                );
    }

    private function calculateDisplayTotals(Invoice $model): array
    {
        $subtotal = (float) $model->subtotal_amount;
        $discountedSubtotal = max(0, $subtotal - (float) $model->discount_amount);
        $cgstAmount = round($discountedSubtotal * ((float) $model->cgst_percent / 100), 2);
        $sgstAmount = round($discountedSubtotal * ((float) $model->sgst_percent / 100), 2);
        $igstAmount = round($discountedSubtotal * ((float) $model->igst_percent / 100), 2);
        return [
            'subtotal' => $subtotal,
            'cgstAmount' => $cgstAmount,
            'sgstAmount' => $sgstAmount,
            'igstAmount' => $igstAmount,
            'grandTotal' => (float) $model->total_payable,
        ];
    }

    /**
     * Send invoice email.
     *
     * Note:
     * Current implementation attaches HTML.
     * If PDF invoices are introduced later, replace only this
     * delivery method. Billing/domain logic remains untouched.
     */
    private function sendEmail(User $client, Invoice $model): void
    {
        $totals = $this->calculateDisplayTotals($model);
        $paymentService = new InvoicePaymentService();
        $htmlContent = $this->renderPartial(
                '_invoice_content',
                array_merge(
                        [
                            'model' => $model,
                            'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
                            'outstandingAmount' => $paymentService->getOutstanding($model),
                        ],
                        $totals
                )
        );

        $message = Yii::$app->mailer
                ->compose()
                ->setFrom([
                    Yii::$app->params['adminEmail'] ?? 'admin@miraclewebtechnologies.com'
                    => 'Miracle Billing',
                ])
                ->setTo($client->email)
                ->setSubject('Invoice ' . $model->invoice_number)
                ->setHtmlBody(
                        '<h3>Hello '
                        . Html::encode($model->client_name)
                        . ',</h3>'
                        . '<p>Your invoice <strong>'
                        . Html::encode($model->invoice_number)
                        . '</strong> has been generated.</p>'
                        . '<p>Thank you for choosing '
                        . '<strong>Miracle Web Technologies</strong>.</p>'
                );

        $message->attachContent(
                $htmlContent,
                [
                    'fileName' => 'Invoice_' . $model->invoice_number . '.html',
                    'contentType' => 'text/html',
                ]
        );

        if (!$message->send()) {
            throw new \RuntimeException('Invoice email could not be sent.');
        }
    }
}
