<?php

declare(strict_types=1);

$this->title = 'Update Customer Billing Profile';

$this->params['breadcrumbs'][] = [
    'label' => 'Customer Billing Profiles',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Changes' => 'Invoices already made keep the old details. The new ones are used from the next invoice.',
    'Country' => 'Outside India means export of services: no GST is charged, and State, GSTIN and PAN are not used.',
    'State' => 'Sets the GST state code. When GST applies, a customer in our state pays CGST and SGST; any other state pays IGST.',
    'GSTIN and PAN' => 'Optional. A GSTIN is checked for typing mistakes and must match the chosen state and the PAN.',
    'Is Gst Applicable' => 'Yes adds GST to this customer\'s invoices; No charges none.',
    'Default Discount Amount' => 'A fixed amount taken off every automatic renewal invoice. If it is as large as a whole renewal, that renewal is not invoiced: lower it or bill by hand.',
    'Address, City and PIN' => 'Leaving any of these empty (PIN only counts in India) makes the profile incomplete, and the customer gets daily reminders until it is complete.',
];
?>

<?= $this->render('_form', [
    'model' => $model,
    'usersList' => $usersList,
    'customer' => $customer,
    'canSetEverything' => $canSetEverything,
]);
