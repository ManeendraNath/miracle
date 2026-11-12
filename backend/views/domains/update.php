<?php

declare(strict_types=1);

use yii\helpers\Html;

$domainLabel = $model->domain_url ?: '#' . $model->id;

$this->title = 'Update Domain: ' . $domainLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Domains Registry',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $domainLabel,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update domain registration and renewal information.';
$this->params['help'] = [
    'Expiry Date and Client' => 'Can\'t be changed while a renewal invoice for this domain is waiting for review, unpaid or part-paid: get it paid or cancelled first. The expiry also can\'t be set earlier than the end of a renewal already paid.',
    'Changing the Expiry Date' => 'The new date also becomes the day of the month this domain renews on.',
    'Status' => 'Only Active domains are invoiced and reminded automatically. Saving it as Inactive or Deleted works like the Disable icon on the list: the renewal is dropped and an unpaid renewal invoice for only this domain is cancelled (by Admins; for other staff an Admin is told).',
    'Renewal Base Amount' => 'The price of one renewal cycle, used for the first renewal billed here. Later renewals continue from the last price billed, so change those with Next Renewal Price.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Billing Override' => 'Renewal Cycle (Months) is how long each renewal covers; Renewal Increase (%) raises the price once a year. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices; other staff see them read-only.',
];
?>

<div class="domains-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>