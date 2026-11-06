<?php

declare(strict_types=1);

/** @var common\models\User $client */
/** @var string[] $missing */
/** @var bool $canBeDisabled */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');
?>
Hello <?= $client->username ?>,

Your invoices need your complete billing details.

Still needed: <?= implode(', ', $missing) ?>

<?php if ($baseUrl !== ''): ?>
Complete them here: <?= $baseUrl ?>/account/profile

<?php endif; ?>
This reminder is sent daily until your details are complete.
<?php if ($canBeDisabled): ?>
Accounts with no active service are disabled if the details are not complete within a month of signing up, after at least 7 days' notice.
<?php endif; ?>

Regards,
Miracle Web Technologies
