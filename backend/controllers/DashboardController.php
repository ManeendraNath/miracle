<?php

namespace backend\controllers;

use yii\filters\AccessControl;
use common\models\AuditRequest;
use common\models\Invoice;
use common\models\Transaction;

class DashboardController extends BaseController
{

    /**
     * Renders the master administrative operational metrics matrix interface.
     * Route mapping path: /backend/web/dashboard/index
     *
     * @return string
     */
    public function actionIndex()
    {
        // Execute aggregated calculations utilizing the custom precision query scopes we configured
        $metrics = [
            // 1. Invoicing Core Metrics Block
            'outstandingReceivables' => Invoice::find()->andWhere(['status' => 'Unpaid'])->sum('total_payable') ?: 0.00,
            'overdueInvoicesCount' => Invoice::find()->andWhere(['status' => 'Unpaid'])->andWhere(['<', 'due_date', date('Y-m-d')])->count(),
            // 2. Gateway Ledger Verification Streams
            'totalRevenueCollected' => Invoice::find()->andWhere(['status' => 'Paid'])->sum('total_payable') ?: 0.00,
            'pendingUpiVerifications' => Transaction::find()->andWhere(['gateway_id' => 2, 'status' => 'Pending'])->count(),
            'capturedRevenueToday' => Transaction::find()
                    ->andWhere(['status' => 'Captured'])
                    ->andWhere(['between', 'paid_at', strtotime('today'), strtotime('tomorrow') - 1])
                    ->sum('amount') ?: 0.00,
            // 3. Inbound Agency Pipeline Tracks
            'newInboundLeadsCount' => AuditRequest::find()->andWhere(['status' => 'Pending'])->count(),
        ];

        return $this->render('index', [
                    'metrics' => $metrics
        ]);
    }

    public function actionUpdate()
    {
        return $this->render('update');
    }

    public function actionView()
    {
        return $this->render('view');
    }
}
