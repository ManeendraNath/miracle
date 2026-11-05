Hello <?= $client->username ?>,

This is a reminder that your service:

<?= $serviceName ?>

will expire on:

<?= $expiryDate ?>

Remaining:

<?= $daysBefore === 0 ? 'Expires today' : $daysBefore . ' day(s)' ?>


<?= $billable ? 'We email your renewal invoice before the expiry date. Please pay it in time to avoid interruption or downtime.' : 'Please contact us to renew this service and avoid interruption or downtime.' ?>

Regards,
Miracle Web Technologies