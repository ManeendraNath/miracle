<?php

declare(strict_types=1);

use yii\helpers\Html;

$couponLabel = $model->code ?: '#' . $model->id;

$this->title = 'Update Coupon: ' . $couponLabel;

$this->params['breadcrumbs'][] = [
    'label' => 'Promotional Coupon',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = [
    'label' => $couponLabel,
    'url' => ['view', 'id' => $model->id],
];

$this->params['breadcrumbs'][] = 'Update';
$this->params['subtitle'] = 'Update discount rules, validity and usage limits.';
$this->params['help'] = [
    'Coupon Code' => 'What the customer types; saved in capitals. It can\'t be changed once the coupon has been used: create a new coupon instead.',
    'Discount Type / Discount Value' => 'Fixed Amount takes that many rupees off; Percentage takes that percent (up to 100) off. Only the lines it applies to are discounted, before GST.',
    'Minimum Order Amount' => 'The invoice lines must add up to at least this before discount and GST. 0 means no minimum.',
    'Starts At / Expires At' => 'Optional. Outside these dates the code is refused.',
    'Total Usage Limit / Usage Limit Per Customer' => 'How many invoices in all, and per customer, can use it; 0 means no limit.',
    'Is Active' => 'An Inactive coupon is refused when anyone enters it.',
    'Applies To' => 'Tick service types to discount only those invoice lines; leave all unticked for every line.',
    'Only For These Customers' => 'Leave empty for anyone. Customers chosen here also get the coupon put on their renewal invoices automatically. Only one coupon goes on an invoice (the newest usable one), and none if the invoice already has a discount, such as the customer\'s Default Discount Amount.',
];
?>

<div class="coupon-update">


<?= $this->render('_form', [
    'model' => $model,
]) ?>

</div>