<?php

declare(strict_types=1);

/** @var common\models\Invoice $invoice */
/** @var string $reference */
/** @var string $invoiceUrl */
?>
We could not verify your payment proof

Hello <?= $invoice->client_name ?>,

Please check the payment reference and submit it again, or contact support if the amount was deducted.

Invoice:
<?= $invoice->invoice_number ?>

<?php if ($reference !== ''): ?>

Reference you submitted:
<?= $reference ?>

<?php endif; ?>

View Invoice:
<?= $invoiceUrl ?>
