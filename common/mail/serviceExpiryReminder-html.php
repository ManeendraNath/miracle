<?php
declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var string $type
 * @var string $serviceName
 * @var string $expiryDate
 * @var int $daysRemaining
 * @var common\models\User $user
 * @var string $dashboardUrl
 */
$this->title = 'Service Renewal Reminder';

$typeLabel = match ($type) {
    'domain' => 'Domain Registration',
    'hosting' => 'Web Hosting',
    'maintenance' => 'Website Maintenance',
    default => ucfirst($type),
};

$alertColor = match (true) {
    $daysRemaining <= 1 => '#dc3545',
    $daysRemaining <= 5 => '#fd7e14',
    default => '#1565c0',
};

$serviceIcon = match ($type) {
    'domain' => '🌐',
    'hosting' => '🖥️',
    'maintenance' => '🔧',
    default => '📌',
};
?>

<div
    style="
    background:#fff4e5;
    border-left:5px solid <?= $alertColor ?>;
    padding:15px;
    margin-bottom:25px;
    "
    >
    <strong>
        Service Renewal Reminder
    </strong>
</div>

<h2
    style="
    margin-top:0;
    color:#222;
    "
    >
    Hello <?=
Html::encode(
        $user->username
)
?>,
</h2>

<p>
    Your service is approaching its expiry date.
    Please arrange renewal before the service expires.
</p>

<table
    role="presentation"
    width="100%"
    cellpadding="10"
    cellspacing="0"
    class="responsive-table"
    style="
    border:1px solid #e5e5e5;
    border-collapse:collapse;
    margin:25px 0;
    "
    >

    <tr>
        <td width="40%">
            Service Type
        </td>

        <td>
            <strong>
                <?= $serviceIcon ?>
                <?=
                Html::encode(
                        $typeLabel
                )
                ?>
            </strong>
        </td>
    </tr>

    <tr>
        <td>
            Service Name
        </td>

        <td>
            <strong>
                <?=
                Html::encode(
                        $serviceName
                )
                ?>
            </strong>
        </td>
    </tr>

    <tr>
        <td>
            Expiry Date
        </td>

        <td>
            <strong>
                <?=
                Yii::$app->formatter->asDate(
                        $expiryDate,
                        'php:d M Y'
                )
                ?>
            </strong>
        </td>
    </tr>

    <tr>
        <td>
            Days Remaining
        </td>

        <td
            style="
            color:<?= $alertColor ?>;
            font-size:18px;
            font-weight:bold;
            "
            >
<?= $daysRemaining ?>
            day<?= $daysRemaining > 1 ? 's' : '' ?>
        </td>
    </tr>

</table>

<p>
    Failure to renew before expiry may result in
    service interruption, website downtime,
    DNS issues, hosting suspension,
    or cancellation of support coverage.
</p>

<p
    style="
    text-align:center;
    margin:35px 0;
    "
    >

    <a
        href="<?=
Html::encode(
        $dashboardUrl
)
?>"
        class="button"
        aria-label="Login to Client Area"
        >
        Login to Client Area
    </a>

</p>

<p>
    If a renewal has already been completed,
    you may safely ignore this email.
</p>