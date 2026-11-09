<?php

declare(strict_types=1);

return [
    'id' => 'app-frontend-tests',
    // Tests send forms straight after opening them.
    'params' => ['formMinSeconds' => 0],
    'components' => [
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'showScriptName' => true,
        ],
        'request' => [
            'cookieValidationKey' => 'test',
        ],
        'mailer' => [
            'messageClass' => \yii\symfonymailer\Message::class,
            'viewPath' => '@common/mail',
        ],
    ],
];
