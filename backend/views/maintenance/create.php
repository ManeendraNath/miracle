<?php

declare(strict_types=1);

$this->title = 'Add Maintenance Contract';

$this->params['breadcrumbs'][] = [
    'label' => 'Maintenance Contracts',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'User ID' => 'The client the contract belongs to and who receives its renewal invoices.',
    'Current Expiry Date' => 'The day the contract runs out. It is invoiced automatically a set number of days before this date, and paying that invoice moves the date on by one renewal cycle.',
    'Status' => 'Only Active contracts are invoiced and reminded automatically. Overdue is for non-payment and turns Active again when a renewal invoice is paid; Cancelled is never renewed.',
    'Renewal Base Amount' => 'Starts at the standard yearly price. It is the price of one renewal cycle, used for the first renewal billed here; later renewals continue from the last price billed.',
    'Next Renewal Price (one-time)' => 'The price of the next renewal invoice, for example a negotiated rate. It carries on: later renewals are worked out from it, plus any price increase. Cleared once that renewal is paid. Leave it empty to have the price calculated.',
    'Renewal Cycle Months and Renewal Increase Percent' => 'How long each renewal covers, and how much the price rises every three years. Leave them empty to use the global settings.',
    'Greyed-out price fields' => 'Only Admins can set renewal prices; other staff see them read-only.',
];
?>

<div class="maintenance-create">

<?=
$this->render(
    '_form',
    [
            'model' => $model,
        ]
)
?>

</div>