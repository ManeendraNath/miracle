<?php

declare(strict_types=1);

namespace backend\controllers;

use common\models\CustomerBillingProfile;
use common\models\User;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

final class CustomerBillingProfileController extends BaseController
{

    public function actionIndex()
    {
        $profiles = CustomerBillingProfile::find()
                ->with('user')
                ->orderBy([
                    'legal_name' => SORT_ASC,
                ])
                ->all();

        return $this->render(
                        'index',
                        [
                            'profiles' => $profiles,
                        ]
                );
    }

    public function actionCreate()
    {
        $model = new CustomerBillingProfile();

        if (
                $this->request->isPost && $model->load(
                        $this->request->post()
                )
        ) {
            if ($model->save()) {
                Yii::$app->session->setFlash(
                        'success',
                        'Customer billing profile created successfully.'
                );

                return $this->redirect([
                            'index',
                ]);
            }
        }

        return $this->render(
                        'create',
                        [
                            'model' => $model,
                            'usersList' =>
                            $this->getUsersList(),
                        ]
                );
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if (
                $this->request->isPost && $model->load(
                        $this->request->post()
                ) && $model->save()
        ) {
            Yii::$app->session->setFlash(
                    'success',
                    'Customer billing profile updated successfully.'
            );

            return $this->redirect([
                        'index',
            ]);
        }

        return $this->render(
                        'update',
                        [
                            'model' => $model,
                            'usersList' =>
                            $this->getUsersList(),
                        ]
                );
    }

    protected function findModel(
            int $id
    ): CustomerBillingProfile
    {
        $model = CustomerBillingProfile::findOne($id);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException(
                        'Billing profile does not exist.'
                );
    }

    private function getUsersList(): array
    {
        return ArrayHelper::map(
                        User::find()
                                ->select([
                                    'id',
                                    'username',
                                    'email',
                                ])
                                ->orderBy([
                                    'username' => SORT_ASC,
                                ])
                                ->asArray()
                                ->all(),
                        'id',
                        static function (
                                array $user
                        ): string {
                            return sprintf(
                                    '%s (%s)',
                                    $user['username'],
                                    $user['email']
                            );
                        }
                );
    }
}
