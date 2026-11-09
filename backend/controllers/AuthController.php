<?php

namespace backend\controllers;

use backend\models\AdminPasswordResetRequestForm;
use backend\models\AdminResetPasswordForm;
use backend\models\LoginForm;
use backend\models\TwoFactorForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\ErrorAction;
use yii\web\Response;

/**
 * AuthController
 * Manages security gateways, administrative authentication, and session control loops.
 */
class AuthController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                // Safe fallback handler: Bounces unauthorized requests cleanly back to the login screen
                'denyCallback' => function ($rule, $action) {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    Yii::$app->user->logout();
                    Yii::$app->session->setFlash('error', 'Unauthorized administrator role level.');
                    return Yii::$app->response->redirect(['/auth/login']);
                },
                'rules' => [
                    [
                        'actions' => ['login', 'two-factor', 'error', 'captcha', 'request-password-reset', 'reset-password'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['index'],
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            return $user instanceof \common\models\Admin && $user->isSuperAdmin();
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => ErrorAction::class,
            ],
            'captcha' => [
                'class' => \yii\captcha\CaptchaAction::class,
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Login action.
     *
     * @return string|Response
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard/index']);
        }

        $this->layout = 'blank';
        $model = new LoginForm();

        // Fixed duplicate load tracking method bug
        if ($model->load(Yii::$app->request->post())) {
            if ($model->login()) {
                // Still a guest when the account has two-factor on: the code comes next.
                return $this->redirect(Yii::$app->user->getIsGuest() ? ['auth/two-factor'] : ['dashboard/index']);
            } else {
                // Debug fallback block for verification issues
                // Not the typed username: it may be a password typed into the wrong field.
                Yii::warning('Admin panel login attempt failed.', 'admin-auth');
            }
        }

        $model->password = '';

        return $this->render('login', [
                    'model' => $model,
        ]);
    }

    /**
     * Second login step: the authenticator code for an account with two-factor on.
     *
     * @return string|Response
     */
    public function actionTwoFactor()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard/index']);
        }
        if (TwoFactorForm::pendingAdmin() === null) {
            Yii::$app->session->setFlash('error', 'Your sign-in expired. Please log in again.');
            return $this->redirect(['auth/login']);
        }

        $this->layout = 'blank';
        $model = new TwoFactorForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->redirect(['dashboard/index']);
        }
        $model->code = '';

        return $this->render('two-factor', ['model' => $model]);
    }

    /**
     * "Forgot password": the same answer whether or not the address exists, so accounts can't be discovered.
     *
     * @return string|Response
     */
    public function actionRequestPasswordReset()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard/index']);
        }
        $this->layout = 'blank';
        $model = new AdminPasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            try {
                $model->sendEmail();
            } catch (\Throwable $e) {
                Yii::error(['message' => 'Admin password reset email failed.', 'error' => $e->getMessage()], __METHOD__);
            }
            Yii::$app->session->setFlash('success', 'If that address belongs to an active admin, a reset link is on its way. It expires in one hour.');
            return $this->redirect(['auth/login']);
        }

        return $this->render('request-password-reset', ['model' => $model]);
    }

    /**
     * Sets a new password from the emailed link.
     *
     * @return string|Response
     */
    public function actionResetPassword(string $token)
    {
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['dashboard/index']);
        }
        try {
            $model = new AdminResetPasswordForm($token);
        } catch (InvalidArgumentException) {
            Yii::$app->session->setFlash('error', 'This reset link is invalid or has expired. Please request a new one.');
            return $this->redirect(['auth/request-password-reset']);
        }
        $this->layout = 'blank';
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->resetPassword();
            Yii::$app->session->setFlash('success', 'Your password has been changed. Please log in.');
            return $this->redirect(['auth/login']);
        }
        $model->password = '';

        return $this->render('reset-password', ['model' => $model]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();
        return $this->redirect(Url::base() . '/auth/login');
    }
}
