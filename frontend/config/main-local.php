<?php

declare(strict_types=1);

use yii\web\Cookie;

$secureCookies = filter_var( $_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN);

return [
    'components' => [
        'request' => [
            'cookieValidationKey' => $_ENV['FRONTEND_COOKIE_VALIDATION_KEY'],
            'csrfCookie' => [
                'httpOnly' => true,
                'secure' => $secureCookies,
                'sameSite' => Cookie::SAME_SITE_LAX,
            ],
        ],
        'user' => [
            'identityCookie' => [
                'name' => '_identity-frontend',
                'httpOnly' => true,
                'secure' => $secureCookies,
                'sameSite' => Cookie::SAME_SITE_LAX,
            ],
        ],
        'session' => [
            'cookieParams' => [
                'httpOnly' => true,
                'secure' => $secureCookies,
                'sameSite' => Cookie::SAME_SITE_LAX,
            ],
        ],
    ],
];
