<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $client */
$baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');

echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'Your customer account is ready',
    'intro' => 'Hello ' . $client->username . ', we have created an account for you. Please set your own password, log in and complete your billing details.',
    'rows' => [['Username', $client->username]],
    'button' => $baseUrl !== '' ? ['Set Your Password', $baseUrl . '/site/request-password-reset'] : null,
    'footnote' => 'Accounts with no active service are disabled if the billing details are not complete within a month, after at least 7 days\' notice.',
]);
