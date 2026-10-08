<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\TransactionSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="transaction-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'invoice_id') ?>

    <?= $form->field($model, 'gateway_id') ?>

    <?= $form->field($model, 'gateway_payment_id') ?>

    <?= $form->field($model, 'gateway_order_id') ?>

    <?php // echo $form->field($model, 'amount') ?>

    <?php // echo $form->field($model, 'gateway_fee') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'user_payment_notes') ?>

    <?php // echo $form->field($model, 'payment_receipt_file') ?>

    <?php // echo $form->field($model, 'raw_payload') ?>

    <?php // echo $form->field($model, 'paid_at') ?>

    <?php // echo $form->field($model, 'created_at') ?>

    <?php // echo $form->field($model, 'updated_at') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
