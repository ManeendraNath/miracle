<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="audit-request-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'company_url')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'current_framework')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'hosting_environment')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'message')->textarea(['rows' => 6]) ?>

    <?= $form->field($model, 'status')->dropDownList([ 'Pending' => 'Pending', 'In_Review' => 'In Review', 'Contacted' => 'Contacted', 'Closed' => 'Closed', ], ['prompt' => '']) ?>

    <?= $form->field($model, 'user_ip')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'created_at')->textInput() ?>

    <?= $form->field($model, 'updated_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
