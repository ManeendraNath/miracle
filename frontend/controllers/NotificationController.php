<?php

declare(strict_types=1);

namespace frontend\controllers;

use common\models\CustomerNotification;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class NotificationController extends Controller
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
            'verbs' => [
                'class' =>
                VerbFilter::class,
                'actions' => [
                    'mark-read' => ['POST'],
                    'mark-all-read' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        $query = CustomerNotification::find()
                ->where([
                    'user_id' =>
                    (int)
                    Yii::$app->user->id,
                ])
                ->orderBy([
                    'created_at' =>
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

    public function actionView(int $id)
    {
        $model = $this->findOwnedModel($id);

        if (!(bool) $model->is_read) {

            $model->is_read = 1;
            $model->read_at = time();

            $model->save(
                    false,
                    [
                        'is_read',
                        'read_at',
                    ]
            );
        }

        return $this->render(
                        'view',
                        [
                            'model' => $model,
                        ]
                );
    }

    public function actionMarkRead(
            int $id
    )
    {
        $model = $this->findOwnedModel(
                $id
        );

        if (!(bool) $model->is_read) {
            $model->is_read = 1;

            $model->read_at = time();

            $model->save(
                    false,
                    [
                        'is_read',
                        'read_at',
                    ]
            );
        }

        return $this->redirect([
                    'index',
        ]);
    }

    public function actionMarkAllRead()
    {
        CustomerNotification::updateAll(
                [
                    'is_read' => 1,
                    'read_at' => time(),
                ],
                [
                    'user_id' =>
                    (int)
                    Yii::$app->user->id,
                    'is_read' => 0,
                ]
        );

        return $this->redirect([
                    'index',
        ]);
    }

    private function findOwnedModel(
            int $id
    ): CustomerNotification
    {
        $model = CustomerNotification::findOne([
            'id' => $id,
            'user_id' =>
            (int)
            Yii::$app->user->id,
        ]);

        if ($model === null) {
            throw new NotFoundHttpException(
                            'Notification does not exist.'
                    );
        }

        return $model;
    }
}
