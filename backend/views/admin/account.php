<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */

$this->title = 'My Account';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="card card-outline card-primary shadow-sm" style="max-width: 640px;">
    <div class="card-header">
        <h3 class="card-title">
            <?= Html::encode($model->username) ?>
            <span class="badge badge-info ml-2"><?= Html::encode($model->role) ?></span>
        </h3>
    </div>
    <?php $form = ActiveForm::begin(['options' => ['autocomplete' => 'off']]); ?>
    <div class="card-body">
        <?= $form->field($model, 'email')->input('email') ?>
        <?= $form->field($model, 'password')->passwordInput([
            'autocomplete' => 'new-password',
            'placeholder' => 'Leave blank to keep your current password',
        ])->hint('Minimum 8 characters.') ?>
    </div>
    <div class="card-footer text-right">
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary px-4']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
