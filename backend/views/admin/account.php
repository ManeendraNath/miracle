<?php

use Mpdf\QrCode\Output\Svg;
use Mpdf\QrCode\QrCode;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */
/** @var string|null $setupSecret two-factor key shown until the first code confirms it */
/** @var string|null $setupUri */

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
    <?php $form = ActiveForm::begin(['id' => 'account-form', 'options' => ['autocomplete' => 'off']]); ?>
    <div class="card-body">
        <?= $form->field($model, 'email')->input('email') ?>
        <?= $form->field($model, 'password')->passwordInput([
            'autocomplete' => 'new-password',
            'placeholder' => 'Leave blank to keep your current password',
        ])->hint('Minimum 8 characters.') ?>
        <div class="form-group">
            <?= Html::label('Current password', 'current-password', ['class' => 'form-label']) ?>
            <?= Html::passwordInput('current_password', '', ['id' => 'current-password', 'class' => 'form-control', 'autocomplete' => 'current-password', 'required' => true]) ?>
            <small class="form-text text-muted">Required to change your email or password.</small>
        </div>
    </div>
    <div class="card-footer text-right">
        <?= Html::submitButton('Save', ['class' => 'btn btn-primary px-4']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<div class="card card-outline card-secondary shadow-sm" style="max-width: 640px;">
    <div class="card-header">
        <h3 class="card-title">
            Two-factor sign-in
            <?php if ($model->hasTwoFactor()): ?>
                <span class="badge badge-success ml-2">On</span>
            <?php else: ?>
                <span class="badge badge-secondary ml-2">Off</span>
            <?php endif; ?>
        </h3>
    </div>
    <div class="card-body">
        <?php if ($model->hasTwoFactor()): ?>
            <p class="mb-3">Every login asks for a 6-digit code from your authenticator app. To turn it off, enter a current code.</p>
            <?= Html::beginForm(['account'], 'post', ['class' => 'form-inline']) ?>
                <?= Html::hiddenInput('two_factor', 'disable') ?>
                <?= Html::textInput('code', '', ['class' => 'form-control mr-2', 'placeholder' => '6-digit code', 'inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxlength' => 7, 'required' => true, 'aria-label' => 'Authenticator code']) ?>
                <?= Html::submitButton('Turn off', ['class' => 'btn btn-outline-danger']) ?>
            <?= Html::endForm() ?>
        <?php elseif ($setupSecret !== null): ?>
            <p>Scan this with an authenticator app (Google Authenticator, Microsoft Authenticator, 1Password, ...), then enter the code it shows.</p>
            <img class="border p-2 mb-3" width="200" height="200" alt="QR code for your authenticator app"
                 src="data:image/svg+xml;base64,<?= base64_encode((new Svg())->output(new QrCode($setupUri), 200)) ?>">
            <p class="mb-1">Can't scan? Enter this key in the app (time-based):</p>
            <p><code class="h5"><?= Html::encode(trim(chunk_split($setupSecret, 4, ' '))) ?></code></p>
            <p class="small"><?= Html::a('Open in an authenticator app on this device', $setupUri) ?></p>
            <?= Html::beginForm(['account'], 'post', ['class' => 'form-inline']) ?>
                <?= Html::hiddenInput('two_factor', 'enable') ?>
                <?= Html::passwordInput('current_password', '', ['id' => 'two-factor-current-password', 'class' => 'form-control mr-2', 'placeholder' => 'Current password', 'autocomplete' => 'current-password', 'required' => true, 'aria-label' => 'Current password']) ?>
                <?= Html::textInput('code', '', ['class' => 'form-control mr-2', 'placeholder' => '6-digit code', 'inputmode' => 'numeric', 'autocomplete' => 'one-time-code', 'maxlength' => 7, 'required' => true, 'autofocus' => true, 'aria-label' => 'Authenticator code']) ?>
                <?= Html::submitButton('Confirm and turn on', ['class' => 'btn btn-primary mr-2']) ?>
            <?= Html::endForm() ?>
            <?= Html::beginForm(['account'], 'post', ['class' => 'mt-2']) ?>
                <?= Html::hiddenInput('two_factor', 'cancel') ?>
                <?= Html::submitButton('Cancel', ['class' => 'btn btn-link px-0']) ?>
            <?= Html::endForm() ?>
        <?php else: ?>
            <p class="mb-3">Add a second step to your login: after your password, a 6-digit code from an authenticator app on your phone.</p>
            <?= Html::beginForm(['account'], 'post') ?>
                <?= Html::hiddenInput('two_factor', 'setup') ?>
                <?= Html::submitButton('Set up two-factor sign-in', ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
