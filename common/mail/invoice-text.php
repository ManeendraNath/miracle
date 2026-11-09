<?php $daysExpired = $invoice->renewalDaysExpired(); ?>
Hello <?= $invoice->client_name ?>,

<?php if ($daysExpired === null): ?>
A new invoice has been generated.
<?php elseif ($daysExpired > 0): ?>
Your service expired on <?= $invoice->due_date ?>, <?= $daysExpired ?> day(s) ago.
Please pay this renewal invoice now to renew it.
<?php elseif ($daysExpired === 0): ?>
Your service expires today. Please pay this renewal invoice today.
<?php else: ?>
Your renewal invoice has been generated.
Please pay by the expiry date to keep your service running.
<?php endif; ?>

Invoice Number:
<?= $invoice->invoice_number ?>


Total Payable:
INR <?= number_format((float) $invoice->total_payable, 2) ?>

<?php if ($outstanding < (float) $invoice->total_payable): ?>

Outstanding after payments received:
INR <?= number_format($outstanding, 2) ?>

<?php endif; ?>

<?= $daysExpired === null ? 'Due Date' : 'Expiry Date (pay by)' ?>:
<?= $invoice->due_date ?>


A PDF copy of this invoice is attached.

Please sign in to your customer portal
to review invoice details and payment status.

Regards,
Miracle Web Technologies
