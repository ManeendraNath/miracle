<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\Maintenance;
use common\models\ServiceRenewalHistory;
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
        $historyQuery = ServiceRenewalHistory::find()
                ->alias('rh')
                ->innerJoin(
                        ['i' => Invoice::tableName()],
                        'i.id = rh.invoice_id'
                )
                ->where([
                    'i.user_id' => (int) Yii::$app->user->id,
                ])
                ->orderBy([
                    'rh.logged_at' => SORT_DESC,
                    'rh.id' => SORT_DESC,
        ]);

        return $this->render(
                        'index',
                        [
                            'dataProvider' => new ActiveDataProvider([
                                'query' => $historyQuery,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                                    ]),
                            'upcomingServices' => $this->getUpcomingServices(),
                        ]
                );
    }

    private function getUpcomingServices(): array
    {
        $today = date('Y-m-d');
        $until = date('Y-m-d', strtotime('+60 days'));

        $services = [];

        foreach (
                Domains::find()
                        ->where([
                            'user_id' => (int) Yii::$app->user->id,
                        ])
                        ->andWhere([
                            'between',
                            'current_expiry_date',
                            $today,
                            $until,
                        ])
                        ->all()
        as $domain
        ) {

            $services[] = [
                'type' => 'Domain',
                'id' => $domain->id,
                'name' => $domain->domain_url,
                'expiry' => $domain->current_expiry_date,
            ];
        }

        foreach (
                Hosting::find()
                        ->where([
                            'user_id' => (int) Yii::$app->user->id,
                        ])
                        ->andWhere([
                            'between',
                            'current_expiry_date',
                            $today,
                            $until,
                        ])
                        ->all()
        as $hosting
        ) {

            $services[] = [
                'type' => 'Hosting',
                'id' => $hosting->id,
                'name' => $hosting->primary_domain,
                'expiry' => $hosting->current_expiry_date,
            ];
        }

        foreach (
                Maintenance::find()
                        ->where([
                            'user_id' => (int) Yii::$app->user->id,
                        ])
                        ->andWhere([
                            'between',
                            'current_expiry_date',
                            $today,
                            $until,
                        ])
                        ->all()
        as $maintenance
        ) {

            $services[] = [
                'type' => 'Maintenance',
                'id' => $maintenance->id,
                'name' => $maintenance->website_url,
                'expiry' => $maintenance->current_expiry_date,
            ];
        }

        usort(
                $services,
                static fn($a, $b) =>
                strcmp($a['expiry'], $b['expiry'])
        );

        return $services;
    }
}
