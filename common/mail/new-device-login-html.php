<?php

declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\User $user */
/** @var common\models\UserLoginHistory $login */
/** @var string $device */
$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/request-password-reset']);
?>

<div style=" max-width:600px; margin:0 auto; font-family:Arial,sans-serif; background:#ffffff; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden;">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div style="background:#0f172a; color:#ffffff; padding:25px; text-align:center;">
        <h2 style="margin:0;">Miracle Web Technologies</h2>
    </div>

    <div style="padding:30px;">
        <h3>New sign-in to your account</h3>
        <p>Hello <?= Html::encode($user->username) ?>,</p>
        <p>Your account was just signed in to from a device or browser we haven't seen before.</p>
        <table style="border-collapse:collapse; margin:20px 0;">
            <tr><td style="padding:6px 16px 6px 0; color:#64748b;">Device</td><td style="padding:6px 0;"><?= Html::encode($device) ?></td></tr>
            <tr><td style="padding:6px 16px 6px 0; color:#64748b;">IP address</td><td style="padding:6px 0;"><?= Html::encode((string) $login->ip_address) ?></td></tr>
            <tr><td style="padding:6px 16px 6px 0; color:#64748b;">Time</td><td style="padding:6px 0;"><?= Html::encode(Yii::$app->formatter->asDatetime($login->logged_in_at, 'php:d M Y, h:i A')) ?></td></tr>
        </table>
        <p>If this was you, you don't need to do anything.</p>
        <p><strong>If it wasn't you</strong>, reset your password now:</p>
        <p style="text-align:center; margin:30px 0;">
            <a href="<?= Html::encode($resetLink) ?>" style="background:#2563eb; color:#ffffff; text-decoration:none; padding:14px 28px; border-radius:6px; display:inline-block; font-weight:600;">Reset Password</a>
        </p>
    </div>
    <div style="background:#f8fafc; padding:20px; text-align:center; font-size:12px; color:#64748b;">© 2015-<?= date('Y') ?> Miracle Web Technologies</div>
</div>
