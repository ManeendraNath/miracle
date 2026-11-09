<?php

declare(strict_types=1);

/** @var common\models\User $user */
/** @var common\models\UserLoginHistory $login */
/** @var string $device */
?>
New sign-in to your account

Hello <?= $user->username ?>,

Your account was just signed in to from a device or browser we haven't seen before.

Device:     <?= $device ?>

IP address: <?= $login->ip_address ?>

Time:       <?= Yii::$app->formatter->asDatetime($login->logged_in_at) ?>


If this was you, you don't need to do anything.

If it wasn't you, reset your password now:
<?= Yii::$app->urlManager->createAbsoluteUrl(['site/request-password-reset']) ?>


Resetting your password signs out every device. You can also sign out all other devices from Account Security after you sign in:
<?= Yii::$app->urlManager->createAbsoluteUrl(['security/index']) ?>
