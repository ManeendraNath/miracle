<?php

declare(strict_types=1);

/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var bool $viaGateway */
/** @var string $invoiceUrl */
?>
We have refunded your payment.

<?= $viaGateway ? 'It usually reaches your account within 5 to 7 working days.' : 'It was sent back to you directly.' ?>


Invoice:
<?= $invoice->invoice_number ?>


Refunded:
INR <?= number_format($amount, 2) ?>


View Invoice:
<?= $invoiceUrl ?>
