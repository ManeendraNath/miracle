<?php
declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */

$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);
?>

Email Verification

Hello <?= $user->username ?>,

Thank you for registering.

Please verify your email address using the link below:

<?= $verifyLink ?>


If you did not create this account,
no further action is required.
