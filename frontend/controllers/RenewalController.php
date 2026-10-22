<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\Invoice;
use common\models\ServiceRenewalHistory;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;

final class RenewalController extends Controller
{

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
        $query = ServiceRenewalHistory::find()
                ->alias('rh')
                ->innerJoin(
                        ['i' => Invoice::tableName()],
                        'i.id = rh.invoice_id'
                )
                ->where([
                    'i.user_id' =>
                    (int)
                    Yii::$app->user->id,
                ])
                ->orderBy([
                    'rh.logged_at' =>
                    SORT_DESC,
                    'rh.id' =>
                    SORT_DESC,
        ]);

        return $this->render(
                        'index',
                        [
                            'dataProvider' =>
                            new ActiveDataProvider([
                                'query' =>
                                $query,
                                'pagination' => [
                                    'pageSize' => 20,
                                ],
                                    ]),
                        ]
                );
    }
}
