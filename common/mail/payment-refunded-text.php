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
<?= !empty($viaPayPal) ? $invoice->money($amount, true) : 'INR ' . number_format($amount, 2) ?><?= !empty($viaPayPal) ? ($invoice->isUsdPriced() ? ' (returned to your PayPal account)' : ' (returned to your PayPal account in US dollars)') : '' ?>

<?php if ($stillDue > 0): ?>

Balance still due on this invoice:
<?= $invoice->money($stillDue, true) ?>

<?php endif; ?>

<?= str_starts_with((string) $invoice->invoice_number, 'TMP-') ? 'View Payments' : 'View Invoice' ?>:
<?= $invoiceUrl ?>
