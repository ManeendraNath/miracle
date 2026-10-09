<?php
/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var array $usersList Passed from InvoiceController */

$this->title = 'Create Invoice';
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="invoice-create">

    <?= $this->render('_form', [
        'model' => $model,
        'usersList' => $usersList, 
    ]) ?>

</div>
