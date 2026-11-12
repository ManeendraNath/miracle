<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */

$this->title = 'Create Transaction';
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Record a payment received outside the website, for example a bank transfer or UPI payment. It is saved as Pending and only counts on the invoice after you click Approve Payment on the next page.',
    'Invoice' => 'Only unpaid and part-paid invoices are listed.',
    'Payment Method' => 'Always Manual UPI / Bank Transfer here. Razorpay and PayPal payments are recorded automatically.',
    'Payment Reference (UTR)' => 'The UTR or bank reference, so the payment can be matched to your bank statement. A reference can be used only once.',
    'Amount' => 'What was actually received, in rupees.',
    'User Payment Notes' => 'Optional, for staff. The customer does not see notes on a payment entered here.',
];
?>
<div class="transaction-create">

    <?= $this->render('_form', [
        'model' => $model,
        'invoiceList' => $invoiceList,
    ]) ?>

</div>
