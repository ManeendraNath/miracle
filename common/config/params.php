<?php

declare(strict_types=1);

return [
    'adminEmail' => $_ENV['ADMIN_EMAIL'],
    'adminWhatsapp' => $_ENV['ADMIN_WHATSAPP'],
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
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => 0.00,
            /*
            'cgst' => 9.00,
            'sgst' => 9.00,
            'igst' => 18.00,
             * 
             */
        ],
        'payment' => [
            'accountName' => $_ENV['BILLING_BANK_ACCOUNT_NAME'] ?? '',
            'bankName' => $_ENV['BILLING_BANK_NAME'] ?? '',
            'accountNumber' => $_ENV['BILLING_BANK_ACCOUNT_NUMBER'] ?? '',
            'ifsc' => $_ENV['BILLING_BANK_IFSC'] ?? '',
            'upiId' => $_ENV['BILLING_UPI_ID'] ?? '',
        ],
    ],
    'frontendBaseUrl' => rtrim($_ENV['FRONTEND_BASE_URL'] ?? '', '/'),
];
