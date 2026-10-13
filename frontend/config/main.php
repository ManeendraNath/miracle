<?php

declare(strict_types=1);

use yii\web\Request;

$params = array_merge(
        require __DIR__ . '/../../common/config/params.php',
        require __DIR__ . '/../../common/config/params-local.php',
        require __DIR__ . '/params.php',
        require __DIR__ . '/params-local.php'
);

$baseUrl = str_replace('/frontend/web', '', (new Request())->getBaseUrl());

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'name' => \common\components\CustomYii::APPLICATION_NAME,
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'components' => [
        // -----------------------------------------------------
        // ASSETS
        // -----------------------------------------------------
        'assetManager' => [
            'basePath' => '@webroot/assets',
            'baseUrl' => '/assets',
            'linkAssets' => false,
            'appendTimestamp' => true,
        ],
        // -----------------------------------------------------
        // REQUEST
        // -----------------------------------------------------
        'request' => [
            'csrfParam' => '_csrf-frontend',
            'baseUrl' => $baseUrl,
            'class' => 'common\components\Request',
        ],
        // -----------------------------------------------------
        // USER
        // -----------------------------------------------------
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => [
                'name' => '_identity-frontend',
                'httpOnly' => true,
            ],
        ],
        // -----------------------------------------------------
        // SESSION
        // -----------------------------------------------------
        'session' => [
            'name' => 'advanced-frontend',
        ],
        // -----------------------------------------------------
        // LOGGING
        // -----------------------------------------------------
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
        // -----------------------------------------------------
        // ERROR HANDLER
        // -----------------------------------------------------
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        // -----------------------------------------------------
        // URL MANAGER
        // -----------------------------------------------------
        'urlManager' => [
            'baseUrl' => $baseUrl,
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<action>' => 'site/<action>',
                '<controller:[\w\-]+>/<id:\d+>' =>
                '<controller>/view',
                '<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>' =>
                '<controller>/<action>',
                '<controller:[\w\-]+>/<action:[\w\-]+>' =>
                '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];
