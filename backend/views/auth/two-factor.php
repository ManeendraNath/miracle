<?php

/** @var yii\web\View $this */
/** @var backend\models\TwoFactorForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Two-factor sign-in';
?>
<div class="site-login">
    <div class="mt-6 col-lg-12">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>Enter the 6-digit code from your authenticator app.</p>

        <?php $form = ActiveForm::begin(['id' => 'two-factor-form']); ?>

            <?= $form->field($model, 'code')->textInput([
                'autofocus' => true,
                'autocomplete' => 'one-time-code',
                'inputmode' => 'numeric',
                'maxlength' => 7,
            ]) ?>

            <div class="form-group">
                <?= Html::submitButton('Verify', ['class' => 'btn btn-primary btn-block', 'name' => 'verify-button']) ?>
            </div>

        <?php ActiveForm::end(); ?>

        <p class="mt-3"><?= Html::a('Back to login', ['auth/login']) ?></p>
    </div>
</div>
