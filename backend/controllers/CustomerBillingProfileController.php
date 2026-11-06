<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\CustomerBillingProfileSearch;
use common\models\CustomerBillingProfile;
use common\models\User;
use common\services\customer\CustomerBillingDetailsService;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

final class CustomerBillingProfileController extends BaseController
{
    public function actionIndex()
    {
        $searchModel = new CustomerBillingProfileSearch();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $searchModel->search($this->request->queryParams),
        ]);
    }

    /**
     * ?user_id= comes from creating a customer. Managers may only use this path, and only fill in what a customer
     * could (no country, GST or discount): the customer scenario leaves those out.
     */
    public function actionCreate(?int $user_id = null)
    {
        $canSetEverything = self::canAccess(Yii::$app->user->identity, 'customer-billing-profile', 'update');
        $customer = $user_id === null ? null : User::findOne($user_id);
        if ($user_id !== null && $customer === null || $customer === null && !$canSetEverything) {
            throw new NotFoundHttpException('Customer does not exist.');
        }
        if ($customer !== null && CustomerBillingProfile::find()->where(['user_id' => $customer->id])->exists()) {
            Yii::$app->session->setFlash('info', 'This customer already has billing details.');

            return $this->redirect(['/user/view', 'id' => $customer->id]);
        }
        $model = $canSetEverything ? new CustomerBillingProfile(['user_id' => $customer?->id]) : CustomerBillingDetailsService::forUser($customer);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Customer billing profile created successfully.');

            return $this->redirect($customer !== null ? ['/user/view', 'id' => $customer->id] : ['index']);
        }

        return $this->render('create', [
            'model' => $model,
            'usersList' => $this->getUsersList(),
            'customer' => $customer,
            'canSetEverything' => $canSetEverything,
        ]);
    }

    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Customer billing profile updated successfully.');

            return $this->redirect(['index']);
        }

        return $this->render('update', [
            'model' => $model,
            'usersList' => $this->getUsersList(),
            'customer' => null,
            'canSetEverything' => true,
        ]);
    }

    protected function findModel(int $id): CustomerBillingProfile
    {
        $model = CustomerBillingProfile::findOne($id);

        if ($model !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Billing profile does not exist.');
    }

    private function getUsersList(): array
    {
        return ArrayHelper::map(
            User::find()
                ->select(['id', 'username', 'email'])
                ->orderBy([
                    'username' => SORT_ASC,
                ])
                ->asArray()
                ->all(),
            'id',
            static function (array $user): string {
                return sprintf('%s (%s)', $user['username'], $user['email']);
            },
        );
    }
}
