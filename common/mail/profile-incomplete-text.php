<?php

declare(strict_types=1);

/** @var common\models\User $client */
/** @var string[] $missing */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');
?>
Hello <?= $client->username ?>,

We need your complete billing details to issue your invoices.

Still needed: <?= implode(', ', $missing) ?>

<?php if ($baseUrl !== ''): ?>
Complete them here: <?= $baseUrl ?>/account/profile

<?php endif; ?>
This reminder is sent daily until your details are complete.

Regards,
Miracle Web Technologies
