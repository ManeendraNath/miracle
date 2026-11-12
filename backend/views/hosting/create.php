<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Hosting Subscription';

$this->params['breadcrumbs'][] = [
    'label' => 'Hosting Subscriptions',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Add a client hosting account and configure its renewal information.';
$this->params['help'] = [
    'Current Expiry Date' => 'The day the hosting runs out. It is invoiced automatically a set number of days before this date, and paying that invoice moves the date on by one renewal cycle.',
    'Status' => 'Only Active accounts are invoiced and reminded automatically. Suspended / Expired is for non-payment and turns Active again when a renewal invoice is paid; Inactive is never renewed.',
    'No price, no invoice' => 'An account with no Base Renewal Amount and no Next Renewal Price is never invoiced automatically.',
    'Base Renewal Amount' => 'The price of one renewal cycle, used for the first renewal billed here. Later renewals continue from the last price billed, so change those with Next Renewal Price.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Billing Override' => 'Renewal Cycle (Months) is how long each renewal covers; Renewal Increase (%) raises the price every two years. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices and the cPanel password; other staff see the prices read-only and no password field.',
];
?>

<div class="hosting-create">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>