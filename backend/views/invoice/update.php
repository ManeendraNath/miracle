<?php

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var common\models\forms\ManualInvoiceItemForm[] $items */
$this->title = 'Update Invoice: ' . $model->invoice_number;
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->invoice_number, 'url' => ['view', 'number' => $model->invoice_number]];
$this->params['breadcrumbs'][] = 'Update';
$this->params['help'] = [
    'Only an unpaid invoice made by hand, with no payment on it, can be changed. The customer and GST rates stay as they were.',
    'Update Invoice' => 'Saves the changes. If the invoice was already emailed, the customer gets the updated one by email.',
    'Due Date' => 'When payment is due. After this date the invoice counts as overdue on the dashboard.',
    'Category / Service type' => 'The category text is printed on the invoice. The drop-down under it only matters for coupons limited to certain services.',
    'Add Item / red X' => 'Add another line, or remove one. At least one line stays.',
    'Discount Amount' => 'Rupees off before GST. If there is a Coupon Code, the coupon\'s discount replaces this.',
    'Coupon Code' => 'Clearing it also removes its discount, unless you type a new Discount Amount. If the customer changes the coupon while you edit, saving stops and the form reloads with their change.',
    'Total Payable' => 'Only a preview; the saved invoice shows the real total.',
];
?>
<div class="invoice-update">

    <?=
    $this->render('_form', [
        'model' => $model,
        'items' => $items,
        'usersList' => [],
    ])
    ?>

</div>
