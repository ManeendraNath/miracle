<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var frontend\models\ContactForm $model */

use common\components\CustomYii;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\captcha\Captcha;

$this->title = 'Get a Project Quote | Miracle Web Technologies';
$this->params['bare'] = true;
$this->params['meta_description'] = 'Tell Miracle Web Technologies what you want to build or upgrade: a shopping cart, web application or website. We will reply with the next steps.';

$address = array_filter(array_map(static fn($l): string => trim((string) $l, ' ,'), [
    CustomYii::getSetting('address_line_1'),
    CustomYii::getSetting('address_line_2'),
    CustomYii::getSetting('address_line_3'),
]));
$phone = (string) CustomYii::getSetting('mobile_1');
$email = (string) CustomYii::getSetting('email_1');
?>
<div class="site-contact">
    <div class="mw-glow">
        <div class="container mw-page-head">
            <h1 class="mw-h1 mb-3">Tell us about your project</h1>
            <p class="mw-lead mb-0">Describe what you want to build or upgrade, and we'll reply with the next steps. Hosting and domain questions are welcome too.</p>
        </div>
    </div>

    <div class="container py-5">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <?php $form = ActiveForm::begin(['id' => 'contact-form']); ?>
                    <?= $form->field($model, 'name')->textInput(['autocomplete' => 'name'])->label('Full name') ?>
                    <?= $form->field($model, 'email')->textInput(['type' => 'email', 'autocomplete' => 'email'])->label('Email address') ?>
                    <?= $form->field($model, 'subject')->textInput()->label('Subject') ?>
                    <?= $form->field($model, 'body')->textarea(['rows' => 5, 'placeholder' => 'What should it do? Your country, and any deadline or budget, also help.'])->label('Your project') ?>
                    <?= $form->field($model, 'verifyCode')->widget(Captcha::class, [
                        'template' => '<div class="row g-2 align-items-center"><div class="col-auto">{image}</div><div class="col">{input}</div></div>',
                    ])->label('Type the code shown') ?>
                    <?= Html::submitButton('Send project details', ['class' => 'btn btn-primary btn-lg mt-2']) ?>
                <?php ActiveForm::end(); ?>
            </div>
            <div class="col-lg-5">
                <div class="mw-panel mb-4">
                    <h2 class="h4 fw-bold mb-3"><?= Html::encode(CustomYii::getSetting('site_name') ?: 'Miracle Web Technologies') ?></h2>
                    <ul class="mw-contact-list">
                        <?php if ($email !== ''): ?>
                            <li><i class="fas fa-envelope fa-fw" aria-hidden="true"></i><?= Html::mailto(Html::encode($email), $email) ?></li>
                        <?php endif; ?>
                        <?php if ($phone !== ''): ?>
                            <li><i class="fas fa-phone-alt fa-fw" aria-hidden="true"></i><?= Html::a(Html::encode(CustomYii::formatPhone($phone)), 'tel:+' . $phone) ?></li>
                        <?php endif; ?>
                        <?php if ($address): ?>
                            <li><i class="fas fa-map-marker-alt fa-fw" aria-hidden="true"></i><span><?= implode('<br>', array_map([Html::class, 'encode'], $address)) ?><br>India</span></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <p>Have an existing site to improve? <?= Html::a('Upgrade your website', ['site/audit']) ?>.</p>
                <p class="mb-0">Already a customer? <?= Html::a('Log in', ['site/login']) ?> to raise a support ticket.</p>
            </div>
        </div>
    </div>
</div>
