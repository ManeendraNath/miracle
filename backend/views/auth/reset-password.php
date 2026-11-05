<?php

/** @var yii\web\View $this */
/** @var backend\models\AdminResetPasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Set a new password';
?>
<div class="site-login">
    <div class="mt-6 col-lg-12">
        <h1><?= Html::encode($this->title) ?></h1>

        <?php $form = ActiveForm::begin(['id' => 'admin-reset-password-form']); ?>

            <?= $form->field($model, 'password')->passwordInput(['autofocus' => true, 'autocomplete' => 'new-password']) ?>

            <div class="form-group">
                <?= Html::submitButton('Save password', ['class' => 'btn btn-primary btn-block', 'name' => 'save-button']) ?>
            </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
