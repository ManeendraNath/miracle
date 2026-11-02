<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'Reset your password',
    'intro' => 'Hello ' . $user->username . ', we received a request to reset the password for your account.',
    'button' => ['Reset Password', Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token])],
    'footnote' => 'If you did not request this, you can ignore this email. The link expires automatically.',
]);
