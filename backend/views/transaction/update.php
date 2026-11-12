<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */

$this->title = 'Update Transaction: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
$this->params['help'] = [
    'Only a pending UPI / bank or manual payment can be changed. Approve or reject it on its own page.',
    'Invoice ID' => 'Only unpaid and part-paid invoices are listed.',
    'Gateway Payment ID' => 'The UTR or bank reference, so the payment can be matched to your bank statement. A reference can be used only once.',
    'Amount' => 'What was actually received, in rupees.',
    'User Payment Notes' => 'Optional. On a UPI / bank payment the customer uploaded proof for, these are the customer\'s own notes and they see them as theirs, so add to them rather than rewrite them. Otherwise only staff see them.',
];
?>
<div class="transaction-update">

    <?= $this->render('_form', [
        'model' => $model,
        'invoiceList' => $invoiceList,
    ]) ?>

</div>
