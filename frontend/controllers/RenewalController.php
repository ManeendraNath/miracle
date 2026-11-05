<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Maintenance;
use common\models\ServiceRenewalHistory;
use common\services\billing\InvoicePaymentService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;

final class RenewalController extends Controller
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
        $historyQuery = ServiceRenewalHistory::find()
            ->alias('rh')
            ->innerJoin(['i' => Invoice::tableName()], 'i.id = rh.invoice_id')
            ->where([
                'i.user_id' => (int) Yii::$app->user->id,
            ])
            ->andWhere(['not in', 'rh.invoice_id', \common\services\billing\RenewalFulfilmentService::fullyRefundedInvoices()])
            ->orderBy([
                'rh.logged_at' => SORT_DESC,
                'rh.id' => SORT_DESC,
            ]);

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => $historyQuery,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]),
            'upcomingServices' => $this->getUpcomingServices(),
        ]);
    }

    private function getUpcomingServices(): array
    {
        $until = date('Y-m-d', strtotime('+60 days'));
        $sources = [
            ['Domain', Domains::class, 'domain_url', InvoiceItem::TYPE_DOMAIN],
            ['Hosting', Hosting::class, 'primary_domain', InvoiceItem::TYPE_HOSTING],
            ['Maintenance', Maintenance::class, 'website_url', InvoiceItem::TYPE_MAINTENANCE],
        ];

        $paymentService = new InvoicePaymentService();
        $services = [];
        foreach ($sources as [$type, $class, $nameAttribute, $itemType]) {
            // Past-expiry ones stay listed because they most need renewing.
            $models = $class
                ::find()
                ->where(['user_id' => (int) Yii::$app->user->id, 'status' => $class::RENEWABLE_STATUSES])
                ->andWhere(['<=', 'current_expiry_date', $until])
                ->all();
            foreach ($models as $model) {
                $services[] = [
                    'type' => $type,
                    'id' => $model->id,
                    'name' => $model->$nameAttribute,
                    'expiry' => $model->current_expiry_date,
                    'renewal' => $paymentService->renewalState($model, $itemType),
                ];
            }
        }

        usort($services, static fn($a, $b) => strcmp($a['expiry'], $b['expiry']));

        return $services;
    }
}
