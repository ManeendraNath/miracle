<?php
declare(strict_types=1);

/** @var common\models\User $user */
$resetLink = Yii::$app->urlManager
        ->createAbsoluteUrl([
            'site/reset-password',
            'token' => $user->password_reset_token,
        ]);
?>

Password Reset Request

Hello <?= $user->username ?>,

We received a request to reset your account password.

Reset Password:
<?= $resetLink ?>


If you did not request a password reset,
please ignore this email.

For security reasons this link expires automatically.