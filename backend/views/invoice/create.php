<?php

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var array $usersList Passed from InvoiceController */
$this->title = 'Create Invoice';
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Bill a customer by hand for anything that is not an automatic renewal, such as project work. The invoice is issued as Unpaid straight away and emailed to the customer.',
    'Client' => 'The customer. They need a billing profile first: their address and GST rates are taken from it.',
    'Due Date' => 'When payment is due. After this date the invoice counts as overdue on the dashboard.',
    'Category / Service type' => 'The category text is printed on the invoice. The drop-down under it only matters for coupons limited to certain services.',
    'Add Item / red X' => 'Add another line, or remove one. At least one line stays.',
    'Discount Amount' => 'Rupees off before GST. If you enter a Coupon Code, the coupon\'s discount replaces this.',
    'Coupon Code' => 'Optional. Checked when you save; if it doesn\'t fit this customer or these lines, you get an error.',
    'Total Payable' => 'Only a preview: the GST fields stay empty until you save and the coupon discount isn\'t included. The saved invoice shows the real total.',
];
?>
<div class="invoice-create">

    <?=
    $this->render('_form', [
        'model' => $model,
        'items' => $items,
        'usersList' => $usersList,
    ])
    ?>

</div>
