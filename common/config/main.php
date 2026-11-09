<?php

declare(strict_types=1);

return [
    'bootstrap' => [
        \common\bootstrap\MailerBootstrap::class,
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    // Business runs on IST: "today" for reminders, due dates and billing periods.
    'timeZone' => 'Asia/Kolkata',
    'components' => [
        // Connection details are in main-local.php; this only adds to them.
        'db' => [
            // Live only, so a local migration shows up at once. deploy.sh clears it after migrating.
            'enableSchemaCache' => YII_ENV_PROD,
            'schemaCache' => 'schemaCache',
            // READ COMMITTED, not MySQL's default REPEATABLE READ: billing code locks a row
            // (SELECT ... FOR UPDATE) and then reads it, its payments or coupon uses. Under REPEATABLE
            // READ those reads can return data from before the lock was taken, so a payment could
            // overwrite a carry-forward or a single-use coupon could be used twice.
            'on afterOpen' => static function (\yii\base\Event $event): void {
                $event->sender->createCommand('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED')->execute();
            },
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        // Apart from 'cache', so a deploy can clear it without resetting the login throttle counters.
        'schemaCache' => [
            'class' => \yii\caching\FileCache::class,
            'cachePath' => '@runtime/schema-cache',
        ],
        'response' => [
            'on beforeSend' => static function (\yii\base\Event $event): void {
                header_remove('X-Powered-By');
                $event->sender->headers
                    ->set('X-Frame-Options', 'DENY')
                    ->set('X-Content-Type-Options', 'nosniff')
                    ->set('Referrer-Policy', 'strict-origin-when-cross-origin')
                    ->set('Strict-Transport-Security', 'max-age=31536000')
                    // Safe on every page (no inline-script rules): no plugins, no <base> hijack, never framed.
                    // ponytail: no script-src yet; it needs the inline scripts moved out first.
                    ->set('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
            },
        ],
        'formatter' => [
            // Clients abroad need the zone. Written out because ICU's own name for it reads "GMT+5:30".
            'datetimeFormat' => "d MMM yyyy, h:mm a 'IST'",
        ],
    ],
];
