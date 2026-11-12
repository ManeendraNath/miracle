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
        'view' => [
            'class' => \common\components\View::class,
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
                    ->set('Content-Security-Policy', "base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
                // Only scripts the app registered (nonce, common\components\View) and what they load ('strict-dynamic':
                // Razorpay, PayPal). Report-only for now: browsers report what it would block to /site/csp-report.
                // 'unsafe-inline' and https: only matter to browsers too old for nonces, which ignore the rest.
                // ponytail: report-only; enforce (rename the header) once the CSP log stays empty for a week.
                if (\Yii::$app->has('view') && \Yii::$app->view instanceof \common\components\View && \Yii::$app->request instanceof \yii\web\Request) {
                    $event->sender->headers->set('Content-Security-Policy-Report-Only', "script-src 'nonce-" . \Yii::$app->view->getNonce()
                        . "' 'strict-dynamic' https: 'unsafe-inline'; object-src 'none'; base-uri 'self'; report-uri "
                        . \Yii::$app->params['frontendBaseUrl'] . '/site/csp-report');
                }
            },
        ],
        'formatter' => [
            // Clients abroad need the zone. Written out because ICU's own name for it reads "GMT+5:30".
            'datetimeFormat' => "d MMM yyyy, h:mm a 'IST'",
        ],
    ],
];
