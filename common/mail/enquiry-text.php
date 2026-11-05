<?php

declare(strict_types=1);

/** @var string $title */
/** @var array $rows */
/** @var string $message */
// Sent to staff with the enquirer as Reply-To, so the layout must not say "do not reply".
$this->params['replyAllowed'] = true;
?>
<?= $title ?>


<?php foreach ($rows as [$label, $value]): ?>
<?php if (trim((string) $value) !== ''): ?>
<?= $label ?>: <?= $value ?>

<?php endif; ?>
<?php endforeach; ?>

<?= $message ?>

