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

    <!-- 👇 FORWARD THE USER ARRAY HERE TOO -->
    <?= $this->render('_form', [
        'model' => $model,
        'usersList' => $usersList,
    ]) ?>

</div>
