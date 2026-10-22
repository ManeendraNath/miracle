<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\UserLoginHistory;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;

final class SecurityController extends Controller
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
        $query = UserLoginHistory::find()
                ->where([
                    'user_id' =>
                    (int)
                    Yii::$app->user->id,
                ])
                ->orderBy([
                    'logged_in_at' =>
                    SORT_DESC,
                    'id' =>
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
