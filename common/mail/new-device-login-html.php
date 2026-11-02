<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserLoginHistory $login */
/** @var string $device */
echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Security alert',
    'title' => 'New sign-in to your account',
    'intro' => 'Hello ' . $user->username . ', your account was just signed in to from a device or browser we haven\'t seen before.',
    'rows' => [
        ['Device', $device],
        ['IP address', (string) $login->ip_address],
        ['Time', Yii::$app->formatter->asDatetime($login->logged_in_at, 'php:d M Y, h:i A')],
    ],
    'note' => ['If this was you, you don\'t need to do anything. If it wasn\'t, reset your password now.'],
    'button' => ['Reset Password', Yii::$app->urlManager->createAbsoluteUrl(['site/request-password-reset'])],
]);
