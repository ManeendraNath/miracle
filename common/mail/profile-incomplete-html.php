<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\User $client */
/** @var string[] $missing */
/** @var bool $canBeDisabled */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');

echo $this->render('_card', [
    'tone' => 'warning',
    'eyebrow' => 'Your account',
    'title' => 'Please complete your billing details.',
    'intro' => 'Hello ' . $client->username . ', we need your complete billing details to issue your invoices.',
    'rows' => [['Still needed', implode(', ', $missing)]],
    'note' => ['This reminder is sent daily until your details are complete.' . ($canBeDisabled ? ' Accounts with no active service are disabled if the details are not complete within a month of signing up.' : '')],
    'button' => $baseUrl !== '' ? ['Complete Billing Details', $baseUrl . '/account/profile'] : null,
]);
