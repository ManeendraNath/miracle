<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\UserLoginHistorySearch;
use common\models\UserLoginHistory;
use Yii;
use yii\web\NotFoundHttpException;

final class UserLoginHistoryController extends BaseController
{

    public function actionIndex()
    {
        $searchModel = new UserLoginHistorySearch();

        $dataProvider = $searchModel->search(
                Yii::$app->request->queryParams
        );

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id)
    {
        return $this->render('view', [
                    'model' => $this->findModel($id),
        ]);
    }

    private function findModel(
            int $id
    ): UserLoginHistory
    {
        $model = UserLoginHistory::find()
                ->with('user')
                ->where([
                    'id' => $id,
                ])
                ->one();

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(
                        'Login history record does not exist.'
                );
    }
}
