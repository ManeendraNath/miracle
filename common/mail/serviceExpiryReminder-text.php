<?php
declare(strict_types=1);

/**
 * @var string $type
 * @var string $serviceName
 * @var string $expiryDate
 * @var int $daysRemaining
 * @var common\models\User $user
 * @var string $dashboardUrl
 */
?>

SERVICE RENEWAL REMINDER

Hello <?= $user->username ?>,

Your service is approaching expiry.

Service Type:
<?= strtoupper($type) ?>


Service Name:
<?= $serviceName ?>


Expiry Date:
<?=
date(
        'd M Y',
        strtotime($expiryDate)
)
?>


Days Remaining:
<?= $daysRemaining ?>


Please renew before the expiry date to avoid
service interruption.

Client Area:

<?= $dashboardUrl ?>


If renewal has already been completed,
please ignore this email.