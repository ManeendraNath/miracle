Payment Confirmed

Hello <?= $invoice->client_name ?>,

We have verified and recorded your payment.

Invoice:
<?= $invoice->invoice_number ?>


Amount Received:
<?= $invoice->money((float) $transaction->amount - (float) $transaction->refunded_amount, true) ?>
<?php if ($transaction->paidInUsd() !== null && !$invoice->isUsdPriced()): ?>
Paid with PayPal: <?= $transaction->paidInUsd() ?>

<?php endif; ?>


Reference:
<?= $transaction->gateway_payment_id ?: 'Manual Entry'
?>


Outstanding Balance:
<?= $invoice->money((float) $outstandingAmount, true) ?>


View Invoice:

<?= $invoiceUrl ?>


Thank you for your payment.