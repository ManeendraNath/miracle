<?php

return [
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                'viewPath' => '@common/mail',
            ],
        ],
    ],
    'components' => [
        'db' => [
            'class' => \yii\db\Connection::class,
            'dsn' => $_ENV['DB_DSN'],
            'username' => $_ENV['DB_USERNAME'],
            'password' => $_ENV['DB_PASSWORD'],
            'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => filter_var(
                    $_ENV['MAIL_USE_FILE_TRANSPORT'] ?? false,
                    FILTER_VALIDATE_BOOLEAN
            ),
            'transport' => [
                'scheme' => $_ENV['SMTP_SCHEME'] ?? 'smtps',
                'host' => $_ENV['SMTP_HOST'],
                'username' => $_ENV['SMTP_USERNAME'],
                'password' => $_ENV['SMTP_PASSWORD'],
                'port' => (int) ($_ENV['SMTP_PORT'] ?? 465),
                'options' => [
                    'ssl' => [
                        'allow_self_signed' => false,
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ],
                ],
            ],
        ],
        'log' => [
            'targets' => [
                [
                    'class' => yii\log\FileTarget::class,
                    'categories' => [
                        'common\services\notification\WhatsAppService',
                    ],
                    'logFile' => '@runtime/logs/whatsapp.log',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
    ],
];
