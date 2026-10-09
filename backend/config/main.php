<?php

use \yii\web\Request;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

$baseUrl = str_replace('/backend/web', '', (new Request)->getBaseUrl());
return [
    'id' => 'app-backend',
    'name' => \common\components\CustomYii::APPLICATION_NAME,
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'homeUrl' => '/admin/dashboard',
    'bootstrap' => ['log'],
    'modules' => [],
    'defaultRoute' => 'auth/login',
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            //'baseUrl' => $baseUrl,
            //'class' => 'common\components\Request',
            //'web' => '/backend/web',
            //'adminUrl' => '/admin'
        ],
        'user' => [
            'identityClass' => backend\models\Admin::class,
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
            'loginUrl' => ['/auth/login'],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'auth/error',
        ],
        'urlManager' => [
            'baseUrl' => $baseUrl,
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<controller:[\w\-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>' => '<controller>/<action>',
                '<controller:[\w\-]+>/<action:[\w\-]+' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
    'defaultRoute' => 'admin/dashboard/index',
    'container' => [
        'definitions' => [
            \yii\widgets\LinkPager::class => \yii\bootstrap5\LinkPager::class,
        ],
    ],
];
