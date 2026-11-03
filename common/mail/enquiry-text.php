<?php

declare(strict_types=1);

/** @var string $title */
/** @var array $rows */
/** @var string $message */
?>
<?= $title ?>


<?php foreach ($rows as [$label, $value]): ?>
<?php if (trim((string) $value) !== ''): ?>
<?= $label ?>: <?= $value ?>

<?php endif; ?>
<?php endforeach; ?>

<?= $message ?>

