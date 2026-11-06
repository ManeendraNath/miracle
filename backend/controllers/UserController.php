<?php

namespace backend\controllers;

use Yii;
use backend\models\searches\UserSearch;
use common\models\User;
use yii\web\NotFoundHttpException;

class UserController extends BaseController
{
    public function actionIndex()
    {
        $searchModel = new UserSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * ⚡ SECURE ONE-CLICK USER ACTIVATION TOGGLE
     * Switches the profile status between Active (10) and Inactive (0) dynamically.
     */
    public function actionToggleStatus($id)
    {
        $model = $this->findModel($id);

        // Suspended (not Inactive): Inactive is the unverified-email state, which
        // the customer could flip back to Active via the verification link.
        if ((int) $model->status === User::getStatusActive()) {
            $model->status = User::getStatusSuspended();
            Yii::$app->session->setFlash('warning', "Account for profile '{$model->username}' has been suspended successfully.");
        } else {
            $model->status = User::getStatusActive();
            Yii::$app->session->setFlash('success', "Account for profile '{$model->username}' has been activated and unlocked successfully!");
        }

        $model->save(false); // Bypasses password validation restrictions during simple status toggles
        $this->warnAboutOpenBusiness($model);
        return $this->redirect(['index']);
    }

    /**
     * Displays a single User model.
     * @param int $id System ID Reference
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Creates a new User model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new User();
        if (!$this->request->isPost) {
            $model->status = User::getStatusActive(); // staff-created customers verify nothing, so they start usable
        }
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'User created. Now add the billing details, or leave them for the customer to complete after logging in.');
            $this->sendWelcome($model);
            return $this->redirect(['/customer-billing-profile/create', 'user_id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('create', ['model' => $model]);
    }

    /** They didn't sign up, so nothing else tells them they have an account, and its one-month clock is running. */
    private function sendWelcome(User $customer): void
    {
        if ((int) $customer->status !== User::getStatusActive()) {
            return;
        }
        try {
            $sent = Yii::$app->mailer
                ->compose(['html' => '@common/mail/account-created-html', 'text' => '@common/mail/account-created-text'], ['client' => $customer])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($customer->email)
                ->setSubject('Your customer account is ready')
                ->send();
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Welcome email failed.', 'userId' => (int) $customer->id, 'error' => $e->getMessage()], __METHOD__);
            $sent = false;
        }
        if (!$sent) {
            Yii::$app->session->addFlash('warning', 'The welcome email could not be sent: please tell the customer their account exists.');
        }
    }

    /**
     * Updates an existing User model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param int $id System ID Reference
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->clearPlainPassword();

        // Below Admin only the status can change: username, email or password would take over the customer's login.
        if (self::canAccess(Yii::$app->user->identity, 'user', 'credentials')) {
            $loaded = $model->load(Yii::$app->request->post());
        } else {
            $posted = Yii::$app->request->post($model->formName());
            $loaded = is_array($posted);
            if ($loaded) {
                $model->setAttributes(array_intersect_key($posted, ['status' => true]));
            }
        }
        if ($loaded && $model->save()) {
            Yii::$app->session->setFlash('success', 'User updated successfully.');
            $this->warnAboutOpenBusiness($model);
            return $this->redirect(['view', 'id' => $model->id]);
        }
        $model->clearPlainPassword();

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Deletes an existing User model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param int $id System ID Reference
     * @return \yii\web\Response
     * @throws NotFoundHttpException if the model cannot be found
     */

    /** Billing doesn't stop by itself for a suspended or inactive customer, and a deleted one's invoices stay in Outstanding. */
    private function warnAboutOpenBusiness(User $model): void
    {
        $status = (int) $model->status;
        if ($status === User::getStatusActive()) {
            return;
        }
        $services = 0;
        foreach ([\common\models\Domains::class, \common\models\Hosting::class, \common\models\Maintenance::class] as $class) {
            $services += (int) $class::find()->where(['user_id' => (int) $model->id, 'status' => 1])->count();
        }
        $invoices = (int) \common\models\Invoice::find()->where(['user_id' => (int) $model->id, 'status' => [\common\models\Invoice::STATUS_UNPAID, \common\models\Invoice::STATUS_PARTIALLY_PAID]])->count();
        if ($services === 0 && $invoices === 0) {
            return;
        }
        $held = "{$services} active service(s) and {$invoices} open invoice(s)";
        Yii::$app->session->addFlash('warning', $status === User::getStatusDeleted()
            ? "This customer has {$held}. Renewals and reminders have stopped, but open invoices stay in Outstanding until cancelled or written off."
            : "This customer can't log in but has {$held}: renewals and reminders continue. Disable the services or cancel the invoices if they should stop.");
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id System ID Reference
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
