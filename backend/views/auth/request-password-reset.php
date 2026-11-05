<?php

/** @var yii\web\View $this */
/** @var backend\models\AdminPasswordResetRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

$this->title = 'Forgot password';
?>
<div class="site-login">
    <div class="mt-6 col-lg-12">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>Enter your admin email address and we'll send you a link to set a new password.</p>

        <?php $form = ActiveForm::begin(['id' => 'admin-password-reset-form']); ?>

            <?= $form->field($model, 'email')->textInput(['autofocus' => true, 'type' => 'email', 'autocomplete' => 'email']) ?>

            <?= $form->field($model, 'verifyCode')->widget(Captcha::class, ['captchaAction' => 'auth/captcha']) ?>

            <div class="form-group">
                <?= Html::submitButton('Send reset link', ['class' => 'btn btn-primary btn-block', 'name' => 'reset-button']) ?>
            </div>

        <?php ActiveForm::end(); ?>

        <p class="mt-3"><?= Html::a('Back to login', ['auth/login']) ?></p>
    </div>
</div>
