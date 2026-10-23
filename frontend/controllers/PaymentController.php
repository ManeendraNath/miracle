<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Invoice;
use common\models\Transaction;
use common\services\billing\PaymentReceiptPdfService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class PaymentController extends Controller
{
    
    public $layout = 'account';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' =>
                AccessControl::class,
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
        $query = Transaction::find()
                ->alias('t')
                ->innerJoin(
                        ['i' => Invoice::tableName()],
                        'i.id = t.invoice_id'
                )
                ->where([
                    'i.user_id' =>
                    (int) Yii::$app->user->id,
                ])
                ->with('invoice')
                ->orderBy([
                    't.created_at' =>
                    SORT_DESC,
                    't.id' =>
                    SORT_DESC,
        ]);

        $dataProvider = new ActiveDataProvider([
            'query' =>
            $query,
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

    public function actionView(int $id)
    {
        $model = Transaction::find()
                ->alias('t')
                ->innerJoin(
                        ['i' => Invoice::tableName()],
                        'i.id = t.invoice_id'
                )
                ->where([
                    't.id' => $id,
                    'i.user_id' =>
                    (int) Yii::$app->user->id,
                ])
                ->with('invoice')
                ->one();

        if ($model === null) {
            throw new NotFoundHttpException(
                            'Payment does not exist.'
                    );
        }

        return $this->render(
                        'view',
                        [
                            'model' => $model,
                        ]
                );
    }

    public function actionReceipt(int $id)
    {
        $transaction = Transaction::find()
                ->alias('t')
                ->innerJoin(
                        [
                            'i' =>
                            Invoice::tableName(),
                        ],
                        'i.id = t.invoice_id'
                )
                ->where([
                    't.id' =>
                    $id,
                    'i.user_id' =>
                    (int)
                    Yii::$app->user->id,
                    't.status' =>
                    Transaction::STATUS_CAPTURED,
                ])
                ->one();

        if ($transaction === null) {
            throw new NotFoundHttpException('Payment receipt does not exist.');
        }

        $pdf = (new PaymentReceiptPdfService())->render($transaction);

        return Yii::$app->response->sendContentAsFile(
                        $pdf,
                        'Payment_Receipt_' . $transaction->id . '.pdf',
                        [
                            'mimeType' => 'application/pdf',
                            'inline' => false,
                        ]
                );
    }
}
