<?php

declare(strict_types=1);

namespace backend\controllers;

use backend\models\searches\CustomerBillingProfileSearch;
use common\models\CustomerBillingProfile;
use common\models\User;
use common\services\customer\CustomerBillingDetailsService;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

final class CustomerBillingProfileController extends BaseController
{
    private const MANAGER_WINDOW_DAYS = 30;

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
     * could (country and address, but no GST or discount): the customer scenario leaves those out.
     */
    public function actionCreate(?int $user_id = null)
    {
        $canSetEverything = self::canAccess(Yii::$app->user->identity, 'customer-billing-profile', 'update');
        $customer = $user_id === null ? null : User::findOne($user_id);
        if ($user_id !== null && $customer === null || $customer === null && !$canSetEverything) {
            throw new NotFoundHttpException('Customer does not exist.');
        }
        // Below Admin this is for customers just created, not a way to fill in anyone with no profile.
        if (!$canSetEverything && ((int) $customer->status === User::getStatusDeleted() || (int) $customer->created_at < time() - self::MANAGER_WINDOW_DAYS * 86400)) {
            throw new ForbiddenHttpException('Only an Admin can add billing details for an existing customer.');
        }
        if ($customer !== null && CustomerBillingProfile::find()->where(['user_id' => $customer->id])->exists()) {
            Yii::$app->session->setFlash('info', 'This customer already has billing details.');

            return $this->redirect(['/user/view', 'id' => $customer->id]);
        }
        $model = $canSetEverything ? new CustomerBillingProfile(['user_id' => $customer?->id]) : CustomerBillingDetailsService::forUser($customer);

        // Below Admin through the customer path, which tells the admins to review GST.
        $saved = $this->request->isPost && ($canSetEverything ? $model->load($this->request->post()) && $model->save() : CustomerBillingDetailsService::save($model, $this->request->post()));
        if ($saved) {
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
        $owner = $model->user_id;

        // The client is fixed once the profile exists: moving it would put this company's GST details on another customer's invoices.
        $loaded = $this->request->isPost && $model->load($this->request->post());
        $model->user_id = $owner;
        if ($loaded && $model->save()) {
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
