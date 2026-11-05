<?php

declare(strict_types=1);

use yii\web\Cookie;

// Explicit COOKIE_SECURE wins; otherwise on whenever the site runs on https.
$secureCookies = filter_var($_ENV['COOKIE_SECURE'] ?? str_starts_with((string) ($_ENV['FRONTEND_BASE_URL'] ?? ''), 'https://'), FILTER_VALIDATE_BOOLEAN);

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
