<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var \frontend\models\SignupForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'Create a new account';
$this->params['breadcrumbs'][] = $this->title;
$this->params['meta_description'] = 'Create a Miracle Web Technologies customer account.';
$htmlIcon = <<<HTML
{label}<div class="input-group"><span class="input-group-text" aria-hidden="true">%s</span>{input}</div>{error}{hint}
HTML;
$labelOptions = ['class' => 'form-label fw-semibold small'];
?>
<div class="site-signup d-flex align-items-center justify-content-center py-5">
    <div class="card border-0 overflow-hidden login-split-card">
        <div class="row g-0">

            <?= $this->render('_auth_panel', ['title' => 'Create your account', 'text' => 'One account for your websites, hosting and domains with us.', 'portal' => true]) ?>

            <!-- Form panel -->
            <div class="col-md-7">
                <div class="p-4 p-lg-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold mb-1"><?= Html::encode($this->title) ?></h1>
                        <p class="text-body-secondary small">Fill out the fields below to get started</p>
                    </div>

                    <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

                    <div class="mb-3">
                        <?= $form->field($model, 'username', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-user"></i>'),
                            'inputOptions' => [
                                'autofocus' => true,
                                'class' => 'form-control',
                                'placeholder' => 'Choose a username',
                            ],
                        ])->textInput()->label('Username', $labelOptions) ?>
                    </div>

                    <div class="mb-3">
                        <?= $form->field($model, 'email', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-envelope"></i>'),
                            'inputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'email@example.com',
                            ],
                        ])->textInput()->label('Email', $labelOptions) ?>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'password', [
                            'options' => ['class' => 'mb-0'],
                            'template' => sprintf($htmlIcon, '<i class="fas fa-lock"></i>'),
                            'inputOptions' => [
                                'class' => 'form-control',
                                'placeholder' => 'Choose a password',
                            ],
                        ])->passwordInput()->label('Password', $labelOptions) ?>
                    </div>

                    <div class="mb-4">
                        <?= $form->field($model, 'verifyCode')->widget(\yii\captcha\Captcha::class, [
                            'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
                        ])->label('Verification Code', $labelOptions) ?>
                    </div>

                    <div class="d-grid">
                        <?= Html::submitButton(
                            'Create account',
                            [
                                'class' => 'btn login-btn btn-lg rounded-3 text-white',
                                'name' => 'signup-button',
                            ],
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>

                    <div class="text-body-secondary text-center mt-3 small">
                        Already have an account? <?= Html::a('Log in', ['site/login']) ?>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
