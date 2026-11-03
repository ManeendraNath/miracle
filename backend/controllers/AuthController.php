<?php

namespace backend\controllers;

use backend\models\LoginForm;
use backend\models\TwoFactorForm;
use Yii;
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
                        'actions' => ['login', 'two-factor', 'error'],
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
                Yii::error('Admin panel login attempt failed for user: ' . $model->username, 'admin-auth');
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
