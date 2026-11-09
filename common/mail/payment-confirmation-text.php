Payment Confirmed

Hello <?= $invoice->client_name ?>,

We have verified and recorded your payment.

Invoice:
<?= $invoice->invoice_number ?>


Amount Received:
INR <?= number_format((float) $transaction->amount - (float) $transaction->refunded_amount, 2)
?>
<?php if ($transaction->paidInUsd() !== null): ?>
Paid with PayPal: <?= $transaction->paidInUsd() ?>

<?php endif; ?>


Reference:
<?= $transaction->gateway_payment_id ?: 'Manual Entry'
?>


Outstanding Balance:
INR <?= number_format((float) $outstandingAmount, 2) ?>


View Invoice:

<?= $invoiceUrl ?>


Thank you for your payment.