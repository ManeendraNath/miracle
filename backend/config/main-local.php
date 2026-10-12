<?php

$config = [
    'components' => [
        'request' => [
            'cookieValidationKey' => $_ENV['BACKEND_COOKIE_VALIDATION_KEY'],
            'baseUrl' => $_ENV['BACKEND_BASE_URL'] ?? '/admin',
        ],

        'urlManager' => [
            'baseUrl' => $_ENV['BACKEND_BASE_URL'] ?? '/admin',
            'enablePrettyUrl' => true,
            'showScriptName' => false,

            'rules' => [
                '/' => 'dashboard/index',

                '<controller:[\w\-]+>/<id:\d+>' =>
                    '<controller>/view',

                '<controller:[\w\-]+>/<action:[\w\-]+>/<id:\d+>' =>
                    '<controller>/<action>',

                '<controller:[\w\-]+>/<action:[\w\-]+>' =>
                    '<controller>/<action>',
            ],
        ],

        'assetManager' => [
            'baseUrl' =>
                ($_ENV['BACKEND_BASE_URL'] ?? '/admin') . '/assets',

            'basePath' => '@webroot/assets',
            'appendTimestamp' => true,
            'linkAssets' => YII_ENV_DEV,
        ],
    ],
];

if (YII_ENV_DEV) {
    $config['bootstrap'][] = 'debug';

    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
    ];

    $config['bootstrap'][] = 'gii';

    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,

        'allowedIPs' => array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    $_ENV['GII_ALLOWED_IPS'] ?? '127.0.0.1,::1'
                )
            )
        ),
    ];
}

return $config;