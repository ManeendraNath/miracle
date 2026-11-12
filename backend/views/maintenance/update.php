<?php

declare(strict_types=1);

use yii\helpers\Html;

$label = $model->website_url ?: '#' . $model->id;

$this->title = 'Update Maintenance: ' . $label;

$this->params['breadcrumbs'][] = [
    'label' => 'Maintenance Contracts',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $label,
    'url' => [
        'view',
        'id' => $model->id,
    ],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update maintenance and renewal information.';
$this->params['help'] = [
    'Current Expiry Date and User ID' => 'Can\'t be changed while a renewal invoice for this contract is waiting for review, unpaid or part-paid: get it paid or cancelled first. The expiry also can\'t go earlier than the end of a renewal already paid, and a new expiry becomes the day of the month it renews on.',
    'Status' => 'Only Active contracts are invoiced automatically. Saving it as Cancelled works like the Disable icon on the list: the renewal is dropped and an unpaid renewal invoice for only this contract is cancelled (by Admins; for other staff an Admin is told).',
    'Renewal Base Amount' => 'The price of one renewal cycle, used for the first renewal billed here. Later renewals continue from the last price billed, so change those with Next Renewal Price.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Renewal Cycle Months and Renewal Increase Percent' => 'How long each renewal covers, and how much the price rises every three years. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices; other staff see them read-only.',
];
?>

<div class="maintenance-update">


<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>