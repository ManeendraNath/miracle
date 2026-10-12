<?php

return [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => $_ENV['FRONTEND_COOKIE_VALIDATION_KEY'],
        ],
    ],
];
