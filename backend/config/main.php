<?php

$params = array_merge(
        require __DIR__ . '/../../common/config/params.php',
        require __DIR__ . '/../../common/config/params-local.php',
        require __DIR__ . '/params.php',
        require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => \common\components\CustomYii::APPLICATION_NAME,
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'defaultRoute' => 'dashboard/index',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        // Same lock as the billing cron (DATABASE()-scoped GET_LOCK): see BaseController::lockBillingRun().
        'mutex' => [
            'class' => yii\mutex\MysqlMutex::class,
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
            'csrfCookie' => [
                'name' => '_csrf-backend',
                'httpOnly' => true,
            ],
        ],
        'user' => [
            'identityClass' => backend\models\Admin::class,
            // Staff sign in each session; an unattended admin panel signs itself out.
            'enableAutoLogin' => false,
            'authTimeout' => 1800,
            'loginUrl' => ['/auth/login'],
        ],
        'session' => [
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => [],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'auth/error',
        ],
    ],
    'params' => $params,
    'container' => [
        'definitions' => [
            \yii\widgets\LinkPager::class => \yii\bootstrap5\LinkPager::class,
        ],
    ],
];
