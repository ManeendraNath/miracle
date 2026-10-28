<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\AdminNotification;
use Yii;
use yii\data\ActiveDataProvider;

final class AdminNotificationController extends BaseController
{

    public function actionIndex()
    {
        return $this->render(
                        'index',
                        [
                            'dataProvider' =>
                            new ActiveDataProvider([
                                'query' =>
                                        AdminNotification::find()
                                        ->orderBy([
                                            'created_at' => SORT_DESC,
                                        ]),
                                'pagination' => [
                                    'pageSize' => 30,
                                ],
                                    ]),
                        ]
                );
    }

    public function actionRead(int $id)
    {
        $model = AdminNotification::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Notification not found.');
        }
        if (!$model->is_read) {
            $model->is_read = 1;
            $model->read_at = time();
            $model->save(false);
        }
        return $model->route ? $this->redirect($model->route) : $this->redirect(['index']);
    }
}
