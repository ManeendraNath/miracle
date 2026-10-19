<?php
/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var array $usersList */
$this->title = 'Update Invoice: ' . $model->invoice_number;
$this->params['breadcrumbs'][] = ['label' => 'Invoices', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->invoice_number, 'url' => ['view', 'number' => $model->invoice_number]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="invoice-update">

    <?=
    $this->render('_form', [
        'model' => $model,
        'items' => $items,
        'usersList' => $usersList,
    ])
    ?>

</div>
