<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\InvoiceSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="invoice-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'invoice_number') ?>

    <?= $form->field($model, 'client_name') ?>

    <?= $form->field($model, 'client_address_line_1') ?>

    <?php // echo $form->field($model, 'client_address_line_2') ?>

    <?php // echo $form->field($model, 'subtotal_amount') ?>

    <?php // echo $form->field($model, 'coupon_id') ?>

    <?php // echo $form->field($model, 'coupon_code') ?>

    <?php // echo $form->field($model, 'discount_amount') ?>

    <?php // echo $form->field($model, 'cgst_percent') ?>

    <?php // echo $form->field($model, 'sgst_percent') ?>

    <?php // echo $form->field($model, 'igst_percent') ?>

    <?php // echo $form->field($model, 'total_payable') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'due_date') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
