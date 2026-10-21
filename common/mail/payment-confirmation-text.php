<?php
declare(strict_types=1);
?>

Payment Confirmed

Hello <?= $invoice->client_name ?>,

We have verified and recorded your payment.

Invoice: <?= $invoice->invoice_number ?>


Amount received: INR <?=
number_format(
        (float) $transaction->amount,
        2
)
?>


Payment reference:
<?= $transaction->gateway_payment_id ?: 'Recorded payment' ?>


Outstanding balance: INR <?=
number_format(
        (float) $outstandingAmount,
        2
)
?>


View invoice:
<?= $invoiceUrl ?>


Thank you for your payment.

Regards,
Miracle Web Technologies