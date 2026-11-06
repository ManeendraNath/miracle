<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Login to your account';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'Log in to your Miracle Web Technologies customer account.';
$htmlIcon = <<<HTML
{label}<div class="input-group"><span class="input-group-text" aria-hidden="true">%s</span>{input}</div>{error}{hint}
HTML;
$labelOptions = ['class' => 'form-label fw-semibold small'];
?>
<div class="site-login d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 overflow-hidden login-split-card">
        <div class="row g-0">

            <!-- Brand panel -->
            <div class="col-md-5 d-none d-md-flex login-brand-panel text-white">
                <div class="d-flex flex-column justify-content-between p-4 p-lg-5 w-100">
                    <div>
                        <?= Html::img(
                            Yii::getAlias('@web/images/logo.png'),
                            [
                                'alt' => 'Miracle Web Technologies',
                                'class' => 'mb-4',
                                'height' => 64,
                            ],
                        ) ?>
                    </div>
                    <div>
                        <p class="fw-bold mb-3 login-brand-title">
                            Welcome<br>Back
                        </p>
                        <p class="opacity-75 mb-0 login-brand-text">
                            Log in to manage your services, invoices and support requests.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form panel -->
            <div class="col-md-7">
                <div class="p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <!-- Mobile-only logo -->
                        <div class="d-md-none mb-3">
                            <?= Html::img(
                                Yii::getAlias('@web/images/logo.png'),
                                [
                                    'alt' => 'Miracle Web Technologies',
                                    'class' => 'login-mobile-logo',
                                    'height' => 48,
                                ],
                            ) ?>
                        </div>
                        <h1 class="h3 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
                        <p class="text-body-secondary small">Enter your credentials to continue</p>
                    </div>

                    <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                    <div class="mb-3">
                        <?= $form->field($model, 'username', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-user"></i>'),
                            'inputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'username',
                                'autofocus' => true,
                            ],
                        ])->textInput()->label('Your Username', $labelOptions) ?>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'password', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-lock"></i>'),
                            'inputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'Password',
                            ],
                        ])->passwordInput()->label('Your Password', $labelOptions) ?>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'rememberMe')->checkbox() ?>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton(
                            'Login',
                            [
                                'class' => 'btn login-btn btn-lg rounded-3 text-white',
                                'name' => 'login-button',
                            ],
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                    <div class="text-center mt-3">
                        New customer? <?= Html::a('Create an account', ['site/signup']) ?>
                    </div>

                    <div class="text-body-secondary text-center mt-3 small">
                        <?= Html::a('Forgot password?', ['site/request-password-reset']) ?>
                        <span class="mx-1">&middot;</span>
                        <?= Html::a('Resend verification email', ['site/resend-verification-email']) ?>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
