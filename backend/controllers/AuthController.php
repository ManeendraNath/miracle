<?php

namespace backend\controllers;

use backend\models\LoginForm;
use Yii;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
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
        /*'access' => [
            'class' => AccessControl::class,
            // Redirects users who fail the access validation check safely back to login
            'denyCallback' => function ($rule, $action) {
                if (Yii::$app->user->isGuest) {
                    return Yii::$app->response->redirect(['auth/login']);
                }
                Yii::$app->user->logout();
                Yii::$app->session->setFlash('error', 'Unauthorized administrator role.');
                return Yii::$app->response->redirect(['auth/login']);
            },
            'rules' => [
                [
                    // 🔓 ALLOW public access to the login and error views
                    'actions' => ['login', 'error'],
                    'allow' => true,
                    'roles' => ['?'], // Guest access only
                ],
                [
                    // 🔒 ONLY allow logged-in accounts to call logout and index
                    'actions' => ['logout', 'index'],
                    'allow' => true,
                    'roles' => ['@'], 
                    'matchCallback' => function ($rule, $action) {
                        $user = Yii::$app->user->identity;
                        if ($user === null) {
                            return false;
                        }
                        return (isset($user->role) && strtolower($user->role) === 'superadmin') || 
                               strtolower($user->username) === 'superadmin';
                    }
                ],
            ],
        ],*/
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
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->redirect(['dashboard/index']);
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
                return $this->redirect(['dashboard/index']);
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
