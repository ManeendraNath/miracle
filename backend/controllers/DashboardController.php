<?php

namespace backend\controllers;

use common\models\AuditRequest;
use common\models\Coupons;
use common\models\Invoice;
use common\models\Transaction;
use common\models\User;

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
        // Safe Dynamic Check Helper Block to prevent missing model table crashes
        $getAssetCount = function ($className) {
            if (class_exists($className)) {
                return (int) $className::find()->count();
            }
            return 0; // Fallback if table/model does not exist yet
        };
        $getAssetCountWithStatus = function ($className) {
            if (class_exists($className)) {
                return (int) $className::find()->where(['status' => 1])->count();
            }
            return 0; // Fallback if table/model does not exist yet
        };

        // 📊 SAFE RETRIEVAL LOGIC FOR RECENT ACTIVE DATA ROWS
        $recentCoupons = [];
        if (class_exists('\common\models\Coupons')) {
            $recentCoupons = Coupons::find()->orderBy(['coupon_id' => SORT_DESC])->limit(4)->asArray()->all();
        }
        // Execute aggregated calculations utilizing the custom precision query scopes we configured
        $metrics = [
            // 1. Invoicing Core Metrics Block
            'totalInvoicesCount' => (int) Invoice::find()->count(),
            'outstandingReceivables' => Invoice::find()->andWhere(['status' => 'Unpaid'])->sum('total_payable') ?: 0.00,
            'overdueInvoicesCount' => Invoice::find()->andWhere(['status' => 'Unpaid'])->andWhere(['<', 'due_date', date('Y-m-d')])->count(),
            'unpaidBalance' => (float) Invoice::find()->where(['status' => ['Unpaid', 'Partially_Paid']])->sum('total_payable') ?? 0.00,
            // 2. Gateway Ledger Verification Streams
            'totalRevenueCollected' => Invoice::find()->andWhere(['status' => 'Paid'])->sum('total_payable') ?: 0.00,
            'pendingUpiVerifications' => Transaction::find()->andWhere(['gateway_id' => 2, 'status' => 'Pending'])->count(),
            'capturedRevenueToday' => Transaction::find()
                    ->andWhere(['status' => 'Captured'])
                    ->andWhere(['between', 'paid_at', strtotime('today'), strtotime('tomorrow') - 1])
                    ->sum('amount') ?: 0.00,
            // 3. Inbound Agency Pipeline Tracks
            'newInboundLeadsCount' => AuditRequest::find()->andWhere(['status' => 'Pending'])->count(),
            // 4. Clients
            'totalClients' => (int) User::find()->count(),
            // 5. Infrastructure Inventory Corrections
            'activeDomainsCount' => $getAssetCountWithStatus('\common\models\Domains'),
            'activeHostingCount' => $getAssetCountWithStatus('\common\models\Hosting'),
            'underCareAmcCount' => $getAssetCountWithStatus('\common\models\Maintenance'),
            // 6. NEW ADDITIONS: CRM & AUDIENCE SUBSCRIBERS PIPELINE LEDGER
            'subscribersCount' => $getAssetCount('\common\models\Subscribers'),
            'supportMessagesCount' => $getAssetCount('\common\models\Messages'),
            'recentCoupons' => $recentCoupons
        ];

        // 🧮 6-MONTH MONTHLY CASHFLOW PROCESSING TREND LOGGER
        $chartLabels = [];
        $chartDataThisYear = [];
        $chartDataLastYear = [];

        for ($i = 5; $i >= 0; $i--) {
            // Calculate raw dates relative to loop index
            $targetTime = strtotime("-$i months");
            $startOfMonth = date('Y-m-01 00:00:00', $targetTime);
            $endOfMonth = date('Y-m-t 23:59:59', $targetTime);

            // Formats readable month strings labels (e.g., "Oct 2026")
            $chartLabels[] = date('M Y', $targetTime);

            // 💰 Calculate Gross Settled Transactions for Current Year
            $currentYearRevenue = (float) Invoice::find()
                            ->where(['status' => 'Paid'])
                            ->andWhere(['between', 'created_at', $startOfMonth, $endOfMonth])
                            ->sum('total_payable') ?: 0.00;
            $chartDataThisYear[] = $currentYearRevenue;

            // 💰 Calculate Corresponding Month Revenue for Last Year (Comparison Vector)
            $startOfMonthLastYear = date('Y-m-01 00:00:00', strtotime("-$i months -1 year"));
            $endOfMonthLastYear = date('Y-m-t 23:59:59', strtotime("-$i months -1 year"));

            $lastYearRevenue = (float) Invoice::find()
                            ->where(['status' => 'Paid'])
                            ->andWhere(['between', 'created_at', $startOfMonthLastYear, $endOfMonthLastYear])
                            ->sum('total_payable') ?: 0.00;
            $chartDataLastYear[] = $lastYearRevenue;
        }

        // Merge chart dataset elements directly inside your returning arrays mapping
        $metrics['chartLabels'] = $chartLabels;
        $metrics['chartDataThisYear'] = $chartDataThisYear;
        $metrics['chartDataLastYear'] = $chartDataLastYear;

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
