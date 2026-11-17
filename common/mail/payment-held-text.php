<?php

declare(strict_types=1);

/** @var common\models\Invoice $invoice */
/** @var common\models\Transaction $transaction */
/** @var string $invoiceUrl */
?>
We received your payment.

<?= $transaction->isHeldForCurrency() ? 'This invoice is paid in US dollars by PayPal, so this rupee payment has not been applied to it.' : 'This invoice can no longer take payments, so the money has not been applied to it.' ?> Our team will refund it or apply it to another of your invoices.

Invoice:
<?= ucfirst($invoice->customerReference()) ?>


Received:
<?= $transaction->money($transaction->amount) ?>


<?= str_starts_with((string) $invoice->invoice_number, 'TMP-') ? 'View Payments' : 'View Invoice' ?>:
<?= $invoiceUrl ?>
