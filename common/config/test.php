<?php

declare(strict_types=1);

return [
    'id' => 'app-common-tests',
    'basePath' => dirname(__DIR__),
    'components' => [
        // In-memory cache: login throttle counters must not persist between test runs.
        'cache' => [
            'class' => \yii\caching\ArrayCache::class,
        ],
        'user' => [
            'class' => \yii\web\User::class,
            'identityClass' => \common\models\User::class,
        ],
    ],
];
