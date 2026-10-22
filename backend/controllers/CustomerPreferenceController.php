<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\CustomerPreferenceSearch;
use common\models\CustomerPreference;
use common\models\User;
use Yii;
use yii\web\NotFoundHttpException;

final class CustomerPreferenceController extends BaseController
{
    public function actionIndex()
    {
        $searchModel = new CustomerPreferenceSearch();

        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionUpdate(int $userId)
    {
        $user = User::findOne($userId);

        if ($user === null) {
            throw new NotFoundHttpException(
                'Customer account does not exist.'
            );
        }

        /*
         * This intentionally uses the same factory method
         * used by the frontend customer account.
         *
         * If the customer does not yet have a preference
         * record, the application's standard defaults are
         * created automatically.
         */
        $model = CustomerPreference::forUser(
            (int) $user->id
        );

        if (
            Yii::$app->request->isPost
            && $model->load(
                Yii::$app->request->post()
            )
        ) {
            $model->updated_at = time();

            if ($model->save()) {
                Yii::$app->session->setFlash(
                    'success',
                    'Notification preferences updated successfully.'
                );

                return $this->redirect([
                    'index',
                ]);
            }
        }

        return $this->render('update', [
            'model' => $model,
            'user' => $user,
        ]);
    }
}