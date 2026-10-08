<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\CouponsSearch $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="coupons-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'coupon_id') ?>

    <?= $form->field($model, 'coupon_name') ?>

    <?= $form->field($model, 'coupon_description') ?>

    <?= $form->field($model, 'coupon_type') ?>

    <?= $form->field($model, 'coupon_code') ?>

    <?php // echo $form->field($model, 'coupon_amount') ?>

    <?php // echo $form->field($model, 'coupon_minimum_order') ?>

    <?php // echo $form->field($model, 'coupon_start_date') ?>

    <?php // echo $form->field($model, 'coupon_expire_date') ?>

    <?php // echo $form->field($model, 'uses_per_coupon') ?>

    <?php // echo $form->field($model, 'uses_per_user') ?>

    <?php // echo $form->field($model, 'coupon_active') ?>

    <?php // echo $form->field($model, 'date_created') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
