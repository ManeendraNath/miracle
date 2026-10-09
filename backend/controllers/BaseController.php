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
                    // Send unauthorized or guest users straight to your custom auth/login page route
                    return Yii::$app->response->redirect(['auth/login']);
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
    
    // ✅ DYNAMIC ROLE CHECK: Matches your actual 'role' column values exactly
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
