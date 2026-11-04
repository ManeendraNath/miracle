<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);

echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'Verify your email address',
    // No username here: anyone can sign up with any address, so the name they chose must not reach the inbox.
    'intro' => 'Hello, please confirm your email address to activate your account.',
    'button' => ['Verify Email', $verifyLink],
    'footnote' => 'If the button doesn\'t work, open this link: ' . $verifyLink,
]);
