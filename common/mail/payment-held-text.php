<?php

declare(strict_types=1);

/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var string $invoiceUrl */
?>
We received your payment.

This invoice can no longer take payments, so the money has not been applied to it. Our team will refund it or apply it to another of your invoices.

Invoice:
<?= ucfirst($invoice->customerReference()) ?>


Received:
INR <?= number_format($amount, 2) ?>


<?= str_starts_with((string) $invoice->invoice_number, 'TMP-') ? 'View Payments' : 'View Invoice' ?>:
<?= $invoiceUrl ?>
