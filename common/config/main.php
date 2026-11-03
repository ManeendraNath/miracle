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
    ],
];
