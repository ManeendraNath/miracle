<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \common\models\LoginForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Log in to your account';
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

            <?= $this->render('_auth_panel', ['title' => 'Welcome back', 'text' => 'Sign in to see everything about your services with us, in one place.', 'portal' => true]) ?>

            <!-- Form panel -->
            <div class="col-md-7">
                <div class="p-4 p-lg-5">
                    <div class="text-center mb-4">
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
                                'placeholder' => 'Your username',
                                'autofocus' => true,
                            ],
                        ])->textInput()->label('Username', $labelOptions) ?>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'password', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-lock"></i>'),
                            'inputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'Your password',
                            ],
                        ])->passwordInput()->label('Password', $labelOptions) ?>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'rememberMe')->checkbox() ?>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton(
                            'Log in',
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
