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
        $model = $this->findModelByNumber($number);
        $paymentService = new InvoicePaymentService();

        // Tax lines come from TaxCalculator::forInvoice() in the view (arrears are not taxed).
        return $this->render('view', [
            'model' => $model,
            'capturedAmount' => $paymentService->getCapturedAmount((int) $model->id),
            'outstandingAmount' => $paymentService->getOutstanding($model),
        ]);
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
            try {
                $items = $this->loadManualItems($post);
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
        foreach (is_array($rows) ? $rows : [] as $row) {
            $item = new ManualInvoiceItemForm();
            try {
                // Typed properties: a blank or non-numeric price or quantity can't be assigned.
                $item->load([$item->formName() => is_array($row) ? $row : []]);
            } catch (\TypeError | \ErrorException) {
                throw new \DomainException('Each invoice item needs a valid whole-number quantity and a numeric price.');
            }
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
                    'item_type' => array_keys(InvoiceItem::getServiceTypes()),
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
            $form->item_type = (int) $dbItem->item_type;
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
                'client_country' => $model->client_country,
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
            try {
                $items = $this->loadManualItems($post);
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
     * Writes off an unpaid invoice that can't be cancelled (it carries an earlier balance, or its payment was
     * refunded after the renewal was granted): nothing more is owed on it or on what was carried into it.
     * Refused while money is kept on them or a payment awaits review (InvoicePaymentService::closeBlocker).
     */
    public function actionClose(int $id)
    {
        $model = $this->findModel($id);
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $model->id)->queryScalar();
            $model->refresh();
            if ($model->status !== Invoice::STATUS_UNPAID) {
                throw new \DomainException('Only an unpaid invoice can be closed.');
            }
            $released = (new InvoicePaymentService())->closeAsRefunded($model);
            $dbTransaction->commit();
            Yii::$app->session->setFlash($released > 0 ? 'warning' : 'success', 'Invoice closed: nothing more is owed on it.' . ($released > 0 ? ' Its unpaid renewals are billed again on the next run unless those services are disabled.' : ''));
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }
            Yii::error($e, __METHOD__);
            Yii::$app->session->setFlash('error', $e instanceof \DomainException ? $e->getMessage() : 'The invoice could not be closed.');
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

    private function findModelByNumber(string $number): Invoice
    {
        $model = Invoice::find()->with(['items', 'transactions'])->where(['invoice_number' => $number])->one();
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
}
