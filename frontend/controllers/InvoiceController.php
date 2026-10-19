<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Invoice;
use common\services\billing\InvoicePaymentService;
use common\services\billing\InvoicePdfService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

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

    public function actionView(
            string $number
    )
    {
        $model = $this->findOwnedInvoice(
                $number
        );

        $paymentService = new InvoicePaymentService();

        return $this->render(
                        'view',
                        [
                            'model' =>
                            $model,
                            'capturedAmount' =>
                                    $paymentService
                                    ->getCapturedAmount(
                                            (int) $model->id
                                    ),
                            'outstandingAmount' =>
                                    $paymentService
                                    ->getOutstanding(
                                            $model
                                    ),
                        ]
                );
    }

    private function findOwnedInvoice(
            string $number
    ): Invoice
    {
        $model = Invoice::find()
    ->with([
        'items',
    ])
    ->where([
        'invoice_number' =>
            $number,

        'user_id' =>
            Yii::$app->user->id,
    ])
    ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(
                        'Invoice does not exist.'
                );
    }

    public function actionDownload(
            string $number
    )
    {
        $invoice = $this->findOwnedInvoice(
                $number
        );

        $pdf = (new InvoicePdfService())
                ->render(
                        $invoice
                );

        return Yii::$app->response
                        ->sendContentAsFile(
                                $pdf,
                                'Invoice_'
                                . $invoice->invoice_number
                                . '.pdf',
                                [
                                    'mimeType' =>
                                    'application/pdf',
                                    'inline' =>
                                    false,
                                ]
                        );
    }
}
