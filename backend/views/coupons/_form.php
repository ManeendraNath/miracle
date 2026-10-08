<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Coupons $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="coupons-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'coupon_name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'coupon_description')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'coupon_type')->dropDownList([ 'F' => 'F', 'P' => 'P', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'coupon_code')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'coupon_amount')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'coupon_minimum_order')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'coupon_start_date')->textInput() ?>

    <?= $form->field($model, 'coupon_expire_date')->textInput() ?>

    <?= $form->field($model, 'uses_per_coupon')->textInput() ?>

    <?= $form->field($model, 'uses_per_user')->textInput() ?>

    <?= $form->field($model, 'coupon_active')->dropDownList([ 'Y' => 'Y', 'N' => 'N', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'date_created')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
