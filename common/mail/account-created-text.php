<?php

declare(strict_types=1);

/** @var common\models\User $client */
$baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
?>
Hello <?= $client->username ?>,

We have created a customer account for you. Please set your own password, log in and complete your billing details.

Username: <?= $client->username ?>

<?php if ($baseUrl !== ''): ?>
Set your password: <?= $baseUrl ?>/site/request-password-reset

<?php endif; ?>
Accounts with no active service are disabled if the billing details are not complete within a month, after at least 7 days' notice.

Regards,
Miracle Web Technologies
