Hello <?= $invoice->client_name ?>,

A new invoice has been generated.

Invoice Number:
<?= $invoice->invoice_number ?>


Total Payable:
INR <?= number_format((float) $invoice->total_payable, 2) ?>


Due Date:
<?= $invoice->due_date ?>


A PDF copy of this invoice is attached.

Please sign in to your customer portal
to review invoice details and payment status.

Regards,
Miracle Web Technologies
