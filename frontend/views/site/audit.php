<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\AuditRequest $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

$this->title = 'Upgrade Your Existing Website | Miracle Web Technologies';
$this->params['bare'] = true;
$this->params['meta_description'] = 'Tell Miracle Web Technologies about your current website or web application and what you want to improve: speed, security, design or features.';
?>
<div class="site-audit">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <h1 class="mw-h1 mb-3">Upgrade your existing website</h1>
            <p class="mw-lead mb-0">Tell us about the site you have and what you want to improve: speed, security, design or features. We'll reply by email with what we suggest.</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="mw-prose">
            <?php $form = ActiveForm::begin(['id' => 'infrastructure-audit-form']); ?>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'name')->textInput(['autocomplete' => 'name'])->label('Your name') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'email')->textInput(['type' => 'email', 'autocomplete' => 'email'])->label('Your email') ?>
                    </div>
                </div>
                <?= $form->field($model, 'company_url')->textInput(['placeholder' => 'https://example.com', 'inputmode' => 'url'])->label('Your website') ?>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'current_framework')->dropDownList([
                            'Not sure' => 'Not sure',
                            'WordPress or WooCommerce' => 'WordPress or WooCommerce',
                            'Shopify' => 'Shopify',
                            'Core PHP' => 'Custom PHP',
                            'Laravel' => 'Laravel',
                            'Yii' => 'Yii',
                            'Other' => 'Something else',
                        ])->label('What is it built with?') ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'hosting_environment')->dropDownList([
                            'Not sure' => 'Not sure',
                            'Shared hosting' => 'Shared hosting or cPanel',
                            'Cloud or VPS' => 'Cloud server or VPS',
                            'Own server' => 'Our own server',
                            'Other' => 'Something else',
                        ])->label('Where is it hosted?') ?>
                    </div>
                </div>
                <?= $form->field($model, 'message')->textarea(['rows' => 5, 'placeholder' => 'For example: the site is slow, the checkout is out of date, or the design looks old.'])->label('What do you want to improve?') ?>
                <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
                    'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
                ])->label('Type the code shown') ?>
                <?= Html::submitButton('Send website details', ['class' => 'btn btn-primary btn-lg mt-2']) ?>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
