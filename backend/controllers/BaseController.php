<?php

namespace backend\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class BaseController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'denyCallback' => function ($rule, $action) {
                    if (Yii::$app->user->isGuest) {
                        return Yii::$app->response->redirect(['/auth/login']);
                    }
                    Yii::$app->user->logout();
                    Yii::$app->session->setFlash('error', 'Unauthorized administrator role portfolio.');
                    return Yii::$app->response->redirect(['/auth/login']);
                },
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function ($rule, $action) {
                            $user = Yii::$app->user->identity;
                            if ($user === null) {
                                return false;
                            }
                            // DYNAMIC ROLE CHECK: Matches your actual 'role' database strings perfectly
                            return (isset($user->role) && strtolower($user->role) === 'superadmin') || 
                                   strtolower($user->username) === 'superadmin';
                        }
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'approve' => ['POST'],
                    'reject' => ['POST'],
                ],
            ],
        ];
    }
}
