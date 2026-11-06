<?php

declare(strict_types=1);

namespace frontend\controllers;

use Yii;
use common\models\CustomerPreference;
use common\models\User;
use common\services\customer\CustomerActivityService;
use common\services\customer\CustomerBillingDetailsService;
use frontend\models\ChangePasswordForm;
use frontend\models\ProfileForm;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class AccountController extends Controller
{
    public $layout = 'account';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionProfile()
    {
        $user = $this->findCurrentUser();
        $model = new ProfileForm($user);
        $billingProfile = CustomerBillingDetailsService::forUser($user);
        $post = Yii::$app->request->post();
        if (Yii::$app->request->isPost && $model->load($post) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Your profile has been updated.');
            return $this->redirect(['profile']);
        }
        // Each card is its own form; load() is false for the one that was not posted.
        if (Yii::$app->request->isPost && CustomerBillingDetailsService::save($billingProfile, $post)) {
            Yii::$app->session->setFlash('success', 'Your billing details have been saved.');
            return $this->redirect(['/dashboard/index']);
        }
        return $this->render('profile', ['model' => $model, 'user' => $user, 'billingProfile' => $billingProfile]);
    }

    public function actionChangePassword()
    {
        $user = $this->findCurrentUser();
        $model = new ChangePasswordForm($user);
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post()) && $model->changePassword()) {
            Yii::$app->user->logout();
            Yii::$app->session->setFlash('success', 'Your password has been changed. Please sign in again.');
            return $this->redirect(['/site/login']);
        }
        return $this->render('change-password', ['model' => $model]);
    }

    public function actionPreferences()
    {
        $model = CustomerPreference::forUser((int) Yii::$app->user->id);
        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $model->user_id = (int) Yii::$app->user->id; // user_id is a safe attribute; never trust the POST
            $model->updated_at = time();
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Notification preferences updated.');
                return $this->redirect(['preferences']);
            }
        }
        return $this->render('preferences', ['model' => $model]);
    }

    public function actionActivity()
    {
        $activities = CustomerActivityService::getActivities((int) Yii::$app->user->id, 100);
        return $this->render('activity', ['activities' => $activities]);
    }

    private function findCurrentUser(): User
    {
        $user = User::findOne((int) Yii::$app->user->id);
        if ($user !== null) {
            return $user;
        }
        Yii::$app->user->logout();
        throw new NotFoundHttpException('Customer account does not exist.');
    }
}
