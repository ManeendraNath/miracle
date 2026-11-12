<?php

use Mpdf\QrCode\Output\Svg;
use Mpdf\QrCode\QrCode;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Admin $model */
/** @var string|null $setupSecret two-factor key shown until the first code confirms it */
/** @var string|null $setupUri */
/** @var string[]|null $backupCodes just created, shown this once */

$this->title = 'My Account';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Save' => 'Changes your email or password. Your current password is always needed. A new password signs you out on every other device.',
    'Two-factor sign-in' => 'Required for every staff account: until it is on, every other page brings you back here.',
];
if ($model->hasTwoFactor()) {
    $this->params['help']['Backup codes'] = 'Each one signs you in once without your phone. Create new backup codes if you used most of them or think someone saw them; the old ones stop working.';
    $this->params['help']['Turn off'] = 'Needs a current code or a backup code. Lost your phone and your backup codes? Ask a Superadmin to turn it off for you.';
} elseif ($setupSecret !== null) {
    $this->params['help']['Confirm and turn on'] = 'Scan the QR code with your authenticator app, then enter your current password and the 6-digit code it shows. Save the backup codes shown next.';
    $this->params['help']['Code not accepted'] = 'Check that the time on your phone is set automatically, then try the newest code.';
} else {
    $this->params['help']['Set up two-factor sign-in'] = 'Shows a QR code to scan with an authenticator app such as Google Authenticator or Microsoft Authenticator.';
}
$this->params['help']['Too many failed attempts'] = 'After repeated wrong passwords or codes, changes are blocked for 15 minutes.';
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
        ])->hint('At least 8 characters, including a letter and a number.') ?>
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
            <?php if (!empty($backupCodes)): ?>
                <div class="alert alert-warning">
                    <strong>Your backup codes.</strong> Save them somewhere safe, such as a password manager. Each one works once in place of a code from your app. They won't be shown again.
                    <pre class="mb-0 mt-2 h5"><?= Html::encode(implode("\n", $backupCodes)) ?></pre>
                </div>
            <?php endif; ?>
            <p class="mb-2">Backup codes left: <strong><?= $model->backupCodesLeft() ?></strong>. Creating new ones stops the old ones working.</p>
            <?= Html::beginForm(['account'], 'post', ['class' => 'form-inline mb-4']) ?>
                <?= Html::hiddenInput('two_factor', 'backup-codes') ?>
                <?= Html::passwordInput('current_password', '', ['id' => 'backup-codes-current-password', 'class' => 'form-control mr-2', 'placeholder' => 'Current password', 'autocomplete' => 'current-password', 'aria-label' => 'Current password']) ?>
                <?= Html::submitButton('Create new backup codes', ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::endForm() ?>
            <p class="mb-3">Every login asks for a 6-digit code from your authenticator app. To turn it off, enter a current code or a backup code.</p>
            <?= Html::beginForm(['account'], 'post', ['class' => 'form-inline']) ?>
                <?= Html::hiddenInput('two_factor', 'disable') ?>
                <?= Html::textInput('code', '', ['class' => 'form-control mr-2', 'placeholder' => 'Code or backup code', 'autocomplete' => 'one-time-code', 'maxlength' => 12, 'required' => true, 'aria-label' => 'Code or backup code']) ?>
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
