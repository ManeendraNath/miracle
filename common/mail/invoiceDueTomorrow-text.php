<?php
declare(strict_types=1);

/**
 * @var common\models\Invoice $invoice
 * @var common\models\User $client
 * @var string $clientPortalUrl
 */
?>

PAYMENT DUE REMINDER

Hello <?= $invoice->client_name ?>,

This is a reminder that your invoice is due tomorrow.

Invoice Number:
<?= $invoice->invoice_number ?>


Due Date:
<?=
date(
        'd M Y',
        strtotime($invoice->due_date)
)
?>


Amount Due:
INR <?=
number_format(
        (float) $invoice->total_payable,
        2
)
?>


Please complete payment before the due date
to avoid interruption of services.


VIEW INVOICE

<?= $clientPortalUrl ?>


If payment has already been submitted,
please ignore this reminder.