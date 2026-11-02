<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $user */
$verifyLink = Yii::$app->urlManager->createAbsoluteUrl(['site/verify-email', 'token' => $user->verification_token]);

echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'Verify your email address',
    'intro' => 'Hello ' . $user->username . ', please confirm your email address to activate your account.',
    'button' => ['Verify Email', $verifyLink],
    'footnote' => 'If the button doesn\'t work, open this link: ' . $verifyLink,
]);
