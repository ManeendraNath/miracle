<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\PasswordResetRequestForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Reset your password';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'Request a password reset link to recover access to your account.';
$htmlIcon = <<<HTML
{label}<div class="input-group"><span class="input-group-text" aria-hidden="true">%s</span>{input}</div>{error}{hint}
HTML;
$labelOptions = ['class' => 'form-label fw-semibold small'];
?>
<div class="site-request-password-reset d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 overflow-hidden login-split-card">
        <div class="row g-0">

            <?= $this->render('_auth_panel', ['title' => 'Forgot your password?', 'text' => 'No worries. Enter your email and we will send you a reset link.', 'portal' => false]) ?>

            <!-- Form panel -->
            <div class="col-md-7">
                <div class="p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
                        <p class="text-body-secondary small">A link to reset your password will be sent to your email</p>
                    </div>

                    <?php $form = ActiveForm::begin(['id' => 'request-password-reset-form']); ?>

                    <div class="mb-4">
                        <?= $form->field($model, 'email', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-envelope"></i>'),
                            'inputOptions' => [
                                'autofocus' => true,
                                'class' => 'form-control',
                                'placeholder' => 'email@example.com',
                            ],
                        ])->textInput()->label('Email', $labelOptions) ?>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'verifyCode')->widget(\yii\captcha\Captcha::class, [
                            'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
                        ])->label('Verification Code', $labelOptions) ?>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton(
                            'Send reset link',
                            [
                                'class' => 'btn login-btn btn-lg rounded-3 text-white',
                            ],
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                    <div class="text-body-secondary text-center mt-3 small">
                        Remember your password? <?= Html::a('Log in', ['site/login']) ?>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
