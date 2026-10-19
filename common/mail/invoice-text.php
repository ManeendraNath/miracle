<?php
declare(strict_types=1);
?>

Hello <?= $invoice->client_name ?>,

Invoice: <?= $invoice->invoice_number ?>

Total payable: INR <?=
number_format(
        (float) $invoice->total_payable,
        2
)
?>

Due date: <?= $invoice->due_date ?>

A PDF copy of your invoice is attached.

Please sign in to your customer portal to review your invoice and payment status.

Regards,
Miracle Web Technologies