<?php

declare(strict_types=1);

return [
    'adminEmail' => $_ENV['ADMIN_EMAIL'],
    'adminBillingEmail' => $_ENV['ADMIN_BILLING_EMAIL'] ?? null, // optional BCC on billing mails
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
    'whatsapp' => [
        'provider' => $_ENV['WHATSAPP_PROVIDER'] ?? '',
        'enabled' => filter_var($_ENV['WHATSAPP_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'apiKey' => $_ENV['WHATSAPP_API_KEY'] ?? '',
        'templateNamespace' => '',
        'sender' => $_ENV['SENDER_NAME'],
        'integratedNumber' => $_ENV['PROVIDER_INTEGRATED_NUMBER'] ?? '',
        'adminMobile' => $_ENV['ADMIN_WHATSAPP_MOBILE'] ?? '',
    ],
    // File the cron jobs append their output to (see docs/BILLING_MODULE.md crontab).
    // Empty = <app root>/runtime/logs/billing-cron.log.
    'cronLogPath' => $_ENV['CRON_LOG_PATH'] ?? '',
    // Online payments. Set RAZORPAY_ENABLED=true plus the keys in .env to go live.
    'razorpay' => [
        'enabled' => filter_var($_ENV['RAZORPAY_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'keyId' => $_ENV['RAZORPAY_KEY_ID'] ?? '',
        'keySecret' => $_ENV['RAZORPAY_KEY_SECRET'] ?? '',
        'webhookSecret' => $_ENV['RAZORPAY_WEBHOOK_SECRET'] ?? '',
    ],
];
