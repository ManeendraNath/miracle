<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Domains $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="domains-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'id')->textInput() ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'domain_url')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'registrar')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'registered_date')->textInput() ?>

    <?= $form->field($model, 'current_expiry_date')->textInput() ?>

    <?= $form->field($model, 'status')->textInput() ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
