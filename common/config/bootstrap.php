<?php

declare(strict_types=1);

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

if (file_exists(dirname(__DIR__, 2) . '/.env')) {
    $dotenv = call_user_func(
        ['Dotenv\\Dotenv', 'createImmutable'],
        dirname(__DIR__, 2)
    );
    $dotenv->load();
}
