<?php

declare(strict_types=1);

use yii\helpers\Html;

$this->title = 'Add Domain';

$this->params['breadcrumbs'][] = [
    'label' => 'Domains Registry',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Add a client domain and configure its registration and renewal information.';
$this->params['help'] = [
    'Expiry Date' => 'The day the domain runs out. It is invoiced automatically a set number of days before this date, and paying that invoice moves the date on by one renewal cycle.',
    'No price, no invoice' => 'A domain with no Renewal Base Amount and no Next Renewal Price is never invoiced automatically.',
    'Status' => 'Only Active domains are invoiced and reminded automatically. Inactive and Deleted domains are not renewed; Deleted also hides the domain from the customer.',
    'Renewal Base Amount' => 'The price of one renewal cycle, used for the first renewal billed here. Later renewals continue from the last price billed, so change those with Next Renewal Price.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Billing Override' => 'Renewal Cycle (Months) is how long each renewal covers; Renewal Increase (%) raises the price once a year. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices; other staff see them read-only.',
];
?>

<div class="domains-create">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>