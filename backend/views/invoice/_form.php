<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="invoice-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'invoice_number')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'client_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'client_address_line_1')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'client_address_line_2')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'subtotal_amount')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'coupon_id')->textInput() ?>

    <?= $form->field($model, 'coupon_code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'discount_amount')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'cgst_percent')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'sgst_percent')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'igst_percent')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'total_payable')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'Unpaid' => 'Unpaid', 'Paid' => 'Paid', 'Partially_Paid' => 'Partially Paid', 'Cancelled' => 'Cancelled', 'Refunded' => 'Refunded', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'due_date')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
