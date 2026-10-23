<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
?>

<div style=" max-width:600px; margin:0 auto; font-family:Arial,sans-serif; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
    <div style="background:#0f172a; color:#ffffff; padding:25px; text-align:center;">
        <h2 style="margin:0;">Miracle Web Technologies</h2>
    </div>

    <div style="padding:30px;">
        <h3>Reset Your Password</h3>
        <p>Hello <?= Html::encode($user->username) ?>,</p>
        <p>We received a request to reset the password for your account.</p>
        <p style="text-align:center; margin:35px 0;">
            <a href="<?= Html::encode($resetLink) ?>" style="background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:6px; display:inline-block; font-weight:600;">Reset Password</a>
        </p>
        <p>If you did not request a password reset, safely ignore this email.</p>
        <p>This link expires automatically for security reasons.</p>
    </div>
    <div style="background:#f8fafc; padding:20px; text-align:center; font-size:12px; color:#64748b;">© 2015-<?= date('Y') ?> Miracle Web Technologies</div>
</div>