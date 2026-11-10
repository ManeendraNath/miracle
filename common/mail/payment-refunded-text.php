<?php

declare(strict_types=1);

/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var bool $viaGateway */
/** @var bool $viaPayPal */
/** @var float $stillDue */
/** @var string $invoiceUrl */
?>
We have refunded your payment.

<?= $viaGateway ? 'It usually reaches your account within 5 to 7 working days.' : 'It was sent back to you directly.' ?>


Invoice:
<?= ucfirst($invoice->customerReference()) ?>


Refunded:
INR <?= number_format($amount, 2) ?><?= !empty($viaPayPal) ? ' (returned to your PayPal account in US dollars)' : '' ?>

<?php if ($stillDue > 0): ?>

Balance still due on this invoice:
INR <?= number_format($stillDue, 2) ?>

<?php endif; ?>

<?= str_starts_with((string) $invoice->invoice_number, 'TMP-') ? 'View Payments' : 'View Invoice' ?>:
<?= $invoiceUrl ?>
