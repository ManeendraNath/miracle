<?php

declare(strict_types=1);

use yii\web\Request;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php',
);

$baseUrl = str_replace('/frontend/web', '', (new Request())->getBaseUrl());
// Absolute links in emails (password reset, email verification) use the configured site address, never
// the request's Host header, which a client can forge to have a reset link point at another site.
$siteUrl = parse_url((string) ($params['frontendBaseUrl'] ?? ''));
$hostInfo = isset($siteUrl['scheme'], $siteUrl['host']) ? $siteUrl['scheme'] . '://' . $siteUrl['host'] . (isset($siteUrl['port']) ? ':' . $siteUrl['port'] : '') : null;

return [
    'id' => 'app-frontend',
    'basePath' => dirname(__DIR__),
    'name' => \common\components\CustomYii::APPLICATION_NAME,
    'bootstrap' => ['log'],
    'controllerNamespace' => 'frontend\controllers',
    'on beforeAction' => [\frontend\components\ProfileGate::class, 'check'],
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
                    'except' => ['csp'],
                    'logVars' => [],
                ],
                // SiteController::actionCspReport
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['csp'],
                    'levels' => ['warning'],
                    'logFile' => '@runtime/logs/csp.log',
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
            'hostInfo' => $hostInfo,
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '<action>' => 'site/<action>',
                '<controller:[\w\-]+>/<id:\d+>' => '<controller>/view',
                '<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>' => '<controller>/<action>',
                '<controller:[\w\-]+>/<action:[\w\-]+>' => '<controller>/<action>',
            ],
        ],
    ],
    'params' => $params,
];
