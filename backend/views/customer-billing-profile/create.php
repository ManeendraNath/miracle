<?php

declare(strict_types=1);

$this->title = 'Create Customer Billing Profile';

$this->params['breadcrumbs'][] = [
    'label' => 'Customer Billing Profiles',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Legal Name' => 'The person or company name printed on invoices and used to greet the customer in emails.',
    'Country' => 'Outside India means export of services: no GST is charged, and State, GSTIN and PAN are not used.',
    'State' => 'Sets the GST state code. When GST applies, a customer in our state pays CGST and SGST; any other state pays IGST.',
    'GSTIN and PAN' => 'Optional. A GSTIN is checked for typing mistakes and must match the chosen state and the PAN.',
];
if ($canSetEverything) {
    $this->params['help']['Address, City and PIN'] = 'The form saves without them (PIN only counts in India), but the profile then counts as incomplete and the customer keeps getting daily reminders.';
    $this->params['help']['Is Gst Applicable'] = 'Yes adds GST to this customer\'s invoices; No charges none.';
    $this->params['help']['Default Discount Amount'] = 'A fixed amount taken off every automatic renewal invoice. If it is as large as a whole renewal, that renewal is not invoiced: lower it or bill by hand.';
} else {
    $this->params['help']['GST and discount'] = 'Left for an Admin to set. Admins are told about the new details so they can check GST.';
}
if ($customer !== null) {
    $this->params['help']['Customer Will Complete This'] = 'Skip this for now. The customer is asked to fill it in, and reminded daily until they do.';
}
?>

<?= $this->render('_form', [
    'model' => $model,
    'usersList' => $usersList,
    'customer' => $customer,
    'canSetEverything' => $canSetEverything,
]);
