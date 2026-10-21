<?php

declare(strict_types=1);

?>

Payment Reminder

Hello <?= $invoice->client_name ?>,

Invoice: <?= $invoice->invoice_number ?>


Outstanding: INR <?= number_format(
    (float) $outstandingAmount,
    2
) ?>


Due date: <?= $invoice->due_date ?>


<?php if ($daysUntilDue > 0): ?>
Payment is due in <?= $daysUntilDue ?> day(s).
<?php elseif ($daysUntilDue === 0): ?>
Payment is due today.
<?php else: ?>
Payment is overdue by <?= abs($daysUntilDue) ?> day(s).
<?php endif; ?>


View invoice:
<?= $invoiceUrl ?>


Regards,
Miracle Web Technologies