<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\Maintenance;
use common\models\CustomerNotification;
use common\models\SupportTicket;
use common\models\Transaction;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;

final class DashboardController extends Controller
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
        ];
    }

    public function actionIndex()
    {
        $userId = (int) Yii::$app->user->id;
        $invoiceQuery = Invoice::find()->where(['user_id' => $userId]);
        $totalInvoices = (int) (clone $invoiceQuery)->count();
        $openInvoices = (int) (clone $invoiceQuery)->andWhere(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])->count();
        $openInvoiceModels = (clone $invoiceQuery)->andWhere(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])->all();
        $paymentService = new \common\services\billing\InvoicePaymentService();
        $outstandingTotal = 0.00;
        foreach ($openInvoiceModels as $openInvoice) {
            $outstandingTotal += $paymentService->getOutstanding($openInvoice);
        }
        $recentInvoices = (clone $invoiceQuery)->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])->limit(5)->all();
        $pendingPayments = (int) Transaction::find()
                        ->alias('t')
                        ->innerJoin(['i' => Invoice::tableName()], 'i.id = t.invoice_id')
                        ->where(['i.user_id' => $userId, 't.status' => Transaction::STATUS_PENDING])->count();
        $domainCount = (int) Domains::find()->where(['user_id' => $userId])->count();
        $hostingCount = (int) Hosting::find()->where(['user_id' => $userId])->count();
        $maintenanceCount = (int) Maintenance::find()->where(['user_id' => $userId])->count();
        $unreadNotifications = (int) CustomerNotification::find()->where(['user_id' => $userId, 'is_read' => 0])->count();
        $openSupportTickets = (int) SupportTicket::find()->where(['user_id' => $userId, 'status' => [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS]])->count();
        $upcomingServices = $this->upcomingServices();
        return $this->render(
                        'index',
                        [
                            'totalInvoices' => $totalInvoices,
                            'openInvoices' => $openInvoices,
                            'outstandingTotal' => round($outstandingTotal, 2),
                            'pendingPayments' => $pendingPayments,
                            'recentInvoices' => $recentInvoices,
                            'domainCount' => $domainCount,
                            'hostingCount' => $hostingCount,
                            'maintenanceCount' => $maintenanceCount,
                            'unreadNotifications' => $unreadNotifications,
                            'openSupportTickets' => $openSupportTickets,
                            'upcomingServices' => $upcomingServices,
                            'renewalCount' => count($upcomingServices),
                            'activities' => $this->getRecentActivities(),
                        ]
                );
    }

    private function upcomingServices()
    {
        $upcomingDomains = $this->getServiceModel(Domains::class);
        $upcomingHosting = $this->getServiceModel(Hosting::class);
        $upcomingMaintenance = $this->getServiceModel(Maintenance::class);
        $upcomingServices = [];
        foreach ($upcomingDomains as $model) {
            $upcomingServices[] = $this->fillService('domain', (int) $model->id, (string) $model->domain_url, (string) $model->current_expiry_date);
        }
        foreach ($upcomingHosting as $model) {
            $upcomingServices[] = $this->fillService('hosting', (int) $model->id, (string) $model->primary_domain, (string) $model->current_expiry_date);
        }
        foreach ($upcomingMaintenance as $model) {
            $upcomingServices[] = $this->fillService('maintenance', (int) $model->id, (string) $model->website_url, (string) $model->current_expiry_date);
        }
        usort($upcomingServices, static fn(array $a, array $b): int => strcmp($a['expiry'], $b['expiry']));
        return $upcomingServices;
    }

    private function getServiceModel($class)
    {
        $today = date('Y-m-d');
        $upcomingUntil = date('Y-m-d', strtotime('+30 days'));
        return $class::find()
                        ->where(['user_id' => (int) Yii::$app->user->id])
                        ->andWhere(['between', 'current_expiry_date', $today, $upcomingUntil])
                        ->orderBy(['current_expiry_date' => SORT_ASC])
                        ->all();
    }

    private function fillService(string $type, int $id, string $name, string $expiry)
    {
        return ['type' => $type, 'id' => $id, 'name' => $name, 'expiry' => $expiry];
    }

    private function getRecentActivities(): array
    {
        $userId = (int) Yii::$app->user->id;
        $activities = [];
        $notifications = CustomerNotification::find()
                        ->where(['user_id' => $userId])
                        ->orderBy(['created_at' => SORT_DESC])
                        ->limit(20)->all();
        foreach ($notifications as $notification) {
            $activities[] = [
                'title' => $notification->title,
                'message' => $notification->message,
                'time' => $notification->created_at,
                'type' => $notification->type,
                'route' => $notification->route,
            ];
        }
        usort($activities, static fn($a, $b) => $b['time'] <=> $a['time']);

        return array_slice($activities, 0, 10);
    }
}
