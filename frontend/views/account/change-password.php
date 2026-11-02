<?php

declare(strict_types=1);

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->title = 'Change Password';

$this->params['breadcrumbs'][] = ['label' => 'My Profile', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;

$form = ActiveForm::begin(['id' => 'change-password-form']);
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Change Password</h1>
    <div class="text-body-secondary">You will be signed out and asked to sign in again with your new password.</div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'currentPassword')->passwordInput(['autocomplete' => 'current-password']) ?>
                <?= $form->field($model, 'newPassword')->passwordInput(['autocomplete' => 'new-password'])->hint('Use at least 8 characters.') ?>
                <?= $form->field($model, 'confirmPassword')->passwordInput(['autocomplete' => 'new-password']) ?>
            </div>
            <div class="card-footer bg-transparent py-3 d-flex gap-2">
                <?= Html::submitButton('Change Password', ['class' => 'btn btn-primary px-4']) ?>
                <?= Html::a('Cancel', ['profile'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end(); ?>
