<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $username */
/** @var string $resetUrl */
echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'Reset your admin password',
    'intro' => 'Hello ' . $username . ', we received a request to reset the password for your admin account.',
    'button' => ['Reset Password', $resetUrl],
    'footnote' => 'If you did not request this, you can ignore this email and your password stays the same. The link expires in one hour.',
]);
