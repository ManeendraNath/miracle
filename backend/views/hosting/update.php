<?php

declare(strict_types=1);

use yii\helpers\Html;

$hostingLabel = $model->primary_domain ?: '#' . $model->id;

$this->title = 'Update Hosting: ' . $hostingLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $hostingLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update hosting account, server access and renewal information.';
$this->params['help'] = [
    'Current Expiry Date and Client' => 'Can\'t be changed while a renewal invoice for this account is waiting for review, unpaid or part-paid: get it paid or cancelled first. The expiry also can\'t go earlier than the end of a renewal already paid, and a new expiry becomes the day of the month it renews on.',
    'Status' => 'Only Active accounts are invoiced automatically. Saving it as Inactive works like the Disable icon on the list: the renewal is dropped and an unpaid renewal invoice for only this account is cancelled (by Admins; for other staff an Admin is told).',
    'cPanel Password' => 'The saved password is never shown here. Leave the field blank to keep it; type a new one to replace it.',
    'Base Renewal Amount' => 'The price of one renewal cycle, used for the first renewal billed here. Later renewals continue from the last price billed, so change those with Next Renewal Price.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Billing Override' => 'Renewal Cycle (Months) is how long each renewal covers; Renewal Increase (%) raises the price every two years. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices and the cPanel password; other staff see the prices read-only and no password field.',
];
?>

<div class="hosting-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>