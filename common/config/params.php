<?php

declare(strict_types=1);

return [
    'adminEmail' => $_ENV['ADMIN_EMAIL'],
    'supportEmail' => $_ENV['SUPPORT_EMAIL'],
    'senderEmail' => $_ENV['SENDER_EMAIL'],
    'senderName' => $_ENV['SENDER_NAME'],
    'user.passwordResetTokenExpire' => 3600,
    'user.passwordMinLength' => 8,
    'bsVersion' => '5.x',
    'billing' => [
        'supplierState' => 'Delhi',
        'supplierStateCode' => '07',
        'gst' => [
            'cgst' => 9.00,
            'sgst' => 9.00,
            'igst' => 18.00,
        ],
    ],
];
