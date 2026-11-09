<?php

namespace backend\controllers;

use common\models\AuditRequest;
use common\models\Coupon;
use common\models\Invoice;
use common\models\SupportTicket;
use common\models\Transaction;
use common\models\User;
use Yii;

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
        $getAssetCountWithStatus = function ($className) {
            if (class_exists($className)) {
                return (int) $className
                    ::find()
                    ->where(['status' => 1])
                    ->andWhere(['not in', 'user_id', User::deletedIds()])
                    ->count();
            }
            return 0; // Fallback if table/model does not exist yet
        };

        // 📊 SAFE RETRIEVAL LOGIC FOR RECENT ACTIVE DATA ROWS
        $recentCoupon = [];
        if (class_exists('\common\models\Coupon')) {
            $recentCoupon = Coupon::find()
                ->orderBy(['id' => SORT_DESC])
                ->limit(4)
                ->all();
        }
        // Managers don't see billing figures: don't compute them, so nothing reaches the page.
        $canBilling = self::canAccess(Yii::$app->user->identity, 'invoice');
        // Money actually received (by payment date) and kept: captured payments less refunds, GST included.
        $received = static fn(?int $from, ?int $to): float => (float) Transaction::find()
            ->where(['status' => [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED], 'is_unapplied' => 0])
            ->andFilterWhere(['between', 'paid_at', $from, $to])
            ->sum('amount - refunded_amount');

        // Amount still owed: payable invoices minus what has already been captured
        // against them (part-paid invoices would otherwise count in full).
        $outstanding = !$canBilling ? 0.0 : (float) Yii::$app->db
            ->createCommand(
                '
            SELECT COALESCE(SUM(i.total_payable), 0) - COALESCE((
                SELECT SUM(t.amount - t.refunded_amount) FROM {{%transaction}} t
                JOIN {{%invoice}} i2 ON i2.id = t.invoice_id
                WHERE t.status = :captured AND t.is_unapplied = 0 AND i2.status IN (:unpaid, :partial)
            ), 0)
            FROM {{%invoice}} i
            WHERE i.status IN (:unpaid, :partial)
        ',
            )
            ->bindValues([
                ':captured' => Transaction::STATUS_CAPTURED,
                ':unpaid' => Invoice::STATUS_UNPAID,
                ':partial' => Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->queryScalar();

        // Execute aggregated calculations utilizing the custom precision query scopes we configured
        $metrics = [
            // 1. Invoicing Core Metrics Block
            'totalInvoicesCount' => (int) Invoice::find()->count(),
            'outstandingReceivables' => $outstanding,
            'overdueInvoicesCount' => Invoice::find()
                ->andWhere(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
                ->andWhere(['<', 'due_date', date('Y-m-d')])
                ->count(),
            'unpaidBalance' => $outstanding,
            // 2. Gateway Ledger Verification Streams
            'totalRevenueCollected' => $canBilling ? $received(null, null) : 0.0,
            'paymentsAwaitingReview' => \common\services\billing\InvoicePaymentService::awaitingReview()->count(),
            'capturedRevenueToday' => $canBilling ? $received(strtotime('today'), strtotime('tomorrow') - 1) : 0.0,
            // Taken but counted on no invoice and in no figure above, until staff refund or apply it.
            'unappliedHeld' => !$canBilling ? 0.0 : (float) Transaction::find()
                ->where(['status' => Transaction::STATUS_CAPTURED, 'is_unapplied' => 1])
                ->sum('amount - refunded_amount'),
            // 3. Inbound Agency Pipeline Tracks
            'newInboundLeadsCount' => AuditRequest::find()
                ->andWhere(['status' => 'Pending'])
                ->count(),
            // 4. Clients
            'totalClients' => (int) User::find()->where(['<>', 'status', User::getStatusDeleted()])->count(),
            // 5. Infrastructure Inventory Corrections
            'activeDomainsCount' => $getAssetCountWithStatus('\common\models\Domains'),
            'activeHostingCount' => $getAssetCountWithStatus('\common\models\Hosting'),
            'underCareAmcCount' => $getAssetCountWithStatus('\common\models\Maintenance'),
            // 6. Website enquiries (contact form), waiting for a first look.
            'pendingLeadsCount' => (int) \common\models\Lead::find()->where(['status' => 'Pending'])->count(),
            'recentCoupon' => $recentCoupon,
        ];

        // 🧮 6-MONTH MONTHLY CASHFLOW PROCESSING TREND LOGGER
        $chartLabels = [];
        $chartDataThisYear = [];
        $chartDataLastYear = [];

        for ($i = 5; $canBilling && $i >= 0; $i--) {
            // Calculate raw dates relative to loop index
            $targetTime = strtotime("first day of -$i months"); // "first day of" avoids skipping short months on the 29th-31st
            // created_at is a unix timestamp, so the bounds must be too
            $startOfMonth = strtotime(date('Y-m-01 00:00:00', $targetTime));
            $endOfMonth = strtotime(date('Y-m-t 23:59:59', $targetTime));

            // Formats readable month strings labels (e.g., "Oct 2026")
            $chartLabels[] = date('M Y', $targetTime);

            // Money received in the month (by payment date), less refunds
            $currentYearRevenue = $received($startOfMonth, $endOfMonth);
            $chartDataThisYear[] = $currentYearRevenue;

            // 💰 Calculate Corresponding Month Revenue for Last Year (Comparison Vector)
            $startOfMonthLastYear = strtotime(date('Y-m-01 00:00:00', strtotime("first day of -$i months -1 year")));
            $endOfMonthLastYear = strtotime(date('Y-m-t 23:59:59', strtotime("first day of -$i months -1 year")));

            $lastYearRevenue = $received($startOfMonthLastYear, $endOfMonthLastYear);
            $chartDataLastYear[] = $lastYearRevenue;
        }

        // Merge chart dataset elements directly inside your returning arrays mapping
        $metrics['chartLabels'] = $chartLabels;
        $metrics['chartDataThisYear'] = $chartDataThisYear;
        $metrics['chartDataLastYear'] = $chartDataLastYear;

        $openSupportTickets = (int) SupportTicket::find()
            ->where([
                'status' => [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS],
            ])
            ->count();

        $highPrioritySupportTickets = (int) SupportTicket::find()
            ->where([
                'priority' => SupportTicket::PRIORITY_HIGH,
                'status' => [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS],
            ])
            ->count();

        $recentSupportTickets = SupportTicket::find()
            ->with('user')
            ->orderBy([
                'updated_at' => SORT_DESC,
                'id' => SORT_DESC,
            ])
            ->limit(5)
            ->all();

        return $this->render('index', [
            'metrics' => $metrics,
            'canBilling' => $canBilling,
            'openSupportTickets' => $openSupportTickets,
            'highPrioritySupportTickets' => $highPrioritySupportTickets,
            'recentSupportTickets' => $recentSupportTickets,
        ]);
    }
}
