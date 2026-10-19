<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Invoice;
use common\models\Transaction;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

final class DashboardController extends Controller
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
        $userId = (int) Yii::$app->user->id;
        $invoiceQuery = Invoice::find()->where(['user_id' => $userId]);
        $totalInvoices = (int) (clone $invoiceQuery)->count();
        $openInvoices = (int) (clone $invoiceQuery)
                        ->andWhere([
                            'status' => [
                                Invoice::STATUS_UNPAID,
                                Invoice::STATUS_PARTIALLY_PAID,
                            ],
                        ])
                        ->count();
        $openInvoiceModels = (clone $invoiceQuery)
                ->andWhere([
                    'status' => [
                        Invoice::STATUS_UNPAID,
                        Invoice::STATUS_PARTIALLY_PAID,
                    ],
                ])
                ->all();
        $paymentService = new \common\services\billing\InvoicePaymentService();
        $outstandingTotal = 0.00;
        foreach ($openInvoiceModels as $openInvoice) {
            $outstandingTotal += $paymentService->getOutstanding($openInvoice);
        }
        $recentInvoices = (clone $invoiceQuery)
                ->orderBy([
                    'created_at' => SORT_DESC,
                    'id' => SORT_DESC,
                ])
                ->limit(5)
                ->all();
        $pendingPayments = (int) Transaction::find()
                        ->alias('t')
                        ->innerJoin(['i' => Invoice::tableName()], 'i.id = t.invoice_id')
                        ->where([
                            'i.user_id' => $userId,
                            't.status' => Transaction::STATUS_PENDING,
                        ])
                        ->count();

        return $this->render(
                        'index',
                        [
                            'totalInvoices' => $totalInvoices,
                            'openInvoices' => $openInvoices,
                            'outstandingTotal' => round($outstandingTotal, 2),
                            'pendingPayments' => $pendingPayments,
                            'recentInvoices' => $recentInvoices,
                        ]
                );
    }
}
