<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="transaction-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'invoice_id')->textInput() ?>

    <?= $form->field($model, 'gateway_id')->textInput() ?>

    <?= $form->field($model, 'gateway_payment_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'gateway_order_id')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'amount')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'gateway_fee')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'Pending' => 'Pending', 'Authorized' => 'Authorized', 'Captured' => 'Captured', 'Failed' => 'Failed', 'Refunded' => 'Refunded', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'user_payment_notes')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'payment_receipt_file')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'raw_payload')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'paid_at')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
