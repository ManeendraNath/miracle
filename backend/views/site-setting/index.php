<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use common\services\payment\PayPalService;
use common\services\payment\RazorpayService;

/** @var yii\web\View $this */
/** @var common\models\SiteSetting $model */

$this->title = 'Site Settings';
$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Business details, invoices, online payments, dollar prices and the contact details shown on the website.';
$this->params['help'] = [
    'Business' => 'The business name is shown in the public website\'s header, footer and policy pages.',
    'Invoices' => '"Check renewal invoices before they are sent" holds new renewal invoices under Invoices (Waiting for review) until you send them.',
    'Online payments: who sees which gateway' => 'Decided by the country on the invoice, taken from the client\'s billing profile. India (or no country): Razorpay, if "Razorpay for clients in India" is on. Abroad: Razorpay and/or PayPal, whichever is on.',
    'Payment keys' => 'Gateway keys are never entered here: they stay in the .env file on the server. The badge shows Keys missing, Test keys or Live keys; on the live site customers never get a checkout that uses test keys.',
    'Exchange rate' => 'PayPal charges in US$. With "Use the daily exchange rate" on, the European Central Bank rate less the margin is used; otherwise, or if the daily rate stops updating, "Rate by hand" is used. With no rate at all, PayPal is hidden.',
    'PayPal fees' => 'Built into the US$ price the client sees, so you receive about the full rupee amount. The box at the top of that section shows what a ₹10,000 invoice costs with PayPal today.',
    'Prices already sent' => 'Once an invoice for a client abroad is emailed, its dollar rate is locked until its due date, so changing the rate here doesn\'t change it. A change to the PayPal fee does apply to it.',
    'Contact details, Address, Social links' => 'Shown on the public website (home and contact pages, footer). The main email also receives the contact-form and audit-request emails. Phone numbers take digits only. Leave a social link empty to hide its icon.',
    'Save settings' => 'Saves every section on this page at once.',
];

$razorpay = new RazorpayService();
$paypal = new PayPalService();
$keyStatus = static fn(bool $hasKeys, bool $test, string $envNames): string => match (true) {
    !$hasKeys => '<span class="badge bg-secondary">Keys missing</span><div class="small text-muted mt-1">Add ' . $envNames . ' to .env on the server.</div>',
    $test && YII_ENV_PROD => '<span class="badge bg-warning text-dark">Test keys</span><div class="small text-muted mt-1">Customers never see a test checkout on the live site.</div>',
    $test => '<span class="badge bg-info text-dark">Test keys</span>',
    default => '<span class="badge bg-success">Live keys</span>',
};
$rate = static fn(float $value): string => Html::encode(rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.'));
$market = (float) $model->usd_market_rate;
$rateNow = PayPalService::usdRate();

/** One section: a card with an icon, a title and a line saying what it is for. */
$section = static fn(string $icon, string $title, string $about): string => '<div class="card settings-card shadow-sm mb-4"><div class="card-header bg-white">'
    . '<h3 class="card-title fw-semibold"><span class="settings-icon"><i class="fas ' . $icon . '"></i></span>' . Html::encode($title) . '</h3>'
    . '<div class="small text-muted settings-about">' . Html::encode($about) . '</div></div><div class="card-body">';
$end = '</div></div>';
?>
<div class="setting-index container-fluid pb-5">
    <?php $form = ActiveForm::begin(['id' => 'site-settings-form']); ?>

    <div class="row">
        <div class="col-xl-6">
            <?= $section('fa-building', 'Business', 'Your business name as the website shows it.') ?>
                <?= $form->field($model, 'site_name')->textInput(['placeholder' => 'e.g. Miracle Web Technologies'])->label('Business name') ?>
                <?= $form->field($model, 'site_url')->textInput(['placeholder' => 'https://miraclewebtechnologies.com'])->label('Website address')->hint('Kept for reference; not shown anywhere yet.') ?>
            <?= $end ?>
        </div>
        <div class="col-xl-6">
            <?= $section('fa-file-invoice', 'Invoices', 'How new renewal invoices go out.') ?>
                <?= $form->field($model, 'review_invoices')->checkbox(['switch' => true])->hint('New renewal invoices wait under Invoices (Waiting for review) until you send them. The customer sees nothing until then. The due date stays the service\'s expiry date.') ?>
            <?= $end ?>
        </div>
    </div>

    <?= $section('fa-credit-card', 'Online payments', 'Which online payment each client sees, by the country in their billing profile. UPI / bank transfer with proof upload is always offered.') ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="settings-box h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div><strong>Razorpay</strong> <span class="text-muted small">charges in ₹</span></div>
                        <div class="text-end"><?= $keyStatus($razorpay->isEnabled(), $razorpay->isTestMode(), 'RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET') ?></div>
                    </div>
                    <?= $form->field($model, 'razorpay_india')->checkbox(['switch' => true]) ?>
                    <?= $form->field($model, 'razorpay_abroad', ['options' => ['class' => 'mb-0']])->checkbox(['switch' => true])->hint('Foreign cards need International Payments switched on in your Razorpay account.') ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-box h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div><strong>PayPal</strong> <span class="text-muted small">charges in US$</span></div>
                        <div class="text-end"><?= $keyStatus($paypal->isEnabled(), $paypal->isSandbox(), 'PAYPAL_CLIENT_ID and PAYPAL_SECRET') ?></div>
                    </div>
                    <?= $form->field($model, 'paypal_abroad', ['options' => ['class' => 'mb-0']])->checkbox(['switch' => true])->hint('PayPal India cannot take payments from customers in India, so it is offered to clients abroad only.') ?>
                </div>
            </div>
        </div>
        <p class="small text-muted mt-3 mb-0"><i class="fas fa-key me-1"></i> A gateway works only when it is on here and its keys are in <code>.env</code> on the server.</p>
    <?= $end ?>

    <?= $section('fa-dollar-sign', 'Dollar prices for PayPal', 'How an invoice in rupees becomes the US$ price a client abroad pays with PayPal.') ?>
        <div class="settings-rate d-flex align-items-center mb-4<?= $rateNow > 0 ? '' : ' settings-rate-missing' ?>">
            <i class="fas <?= $rateNow > 0 ? 'fa-calculator' : 'fa-exclamation-triangle' ?> fa-lg me-3"></i>
            <div>
                <?= $rateNow > 0
                    ? 'Now: <strong>₹' . $rate($rateNow) . ' per US$1</strong> before fees. A ₹10,000 invoice is <strong>US$' . PayPalService::chargeFor(10000, $rateNow) . '</strong> with PayPal.'
                    : 'No exchange rate yet: PayPal stays hidden until one is fetched or set by hand.' ?>
            </div>
        </div>
        <h6 class="settings-subhead">Exchange rate</h6>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'usd_rate_auto')->checkbox(['switch' => true])->hint($market > 0
                    ? 'Today: ₹' . $rate($market) . ' per US$1 (European Central Bank, updated ' . Yii::$app->formatter->asRelativeTime((int) $model->usd_rate_fetched_at) . ').'
                    : 'Fetched free from the European Central Bank (Frankfurter) when a PayPal invoice is opened.') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'usd_rate_margin')->input('number', ['step' => '0.1', 'min' => 0, 'max' => 20])->hint('PayPal converts dollars to rupees about 3–4% below the market rate. Charging this much more covers it.') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'usd_inr_rate')->input('number', ['step' => '0.0001', 'min' => 10])->hint('Used when the daily rate is off or could not be fetched.') ?>
            </div>
        </div>
        <h6 class="settings-subhead">PayPal fees</h6>
        <p class="small text-muted">Both fees are built into the US$ price the client sees (one total, never a separate "PayPal fee"), so you receive about the full rupee amount.</p>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'paypal_fee_percent')->input('number', ['step' => '0.01', 'min' => 0, 'max' => 15])->hint('PayPal\'s fee on payments from abroad (about 4.4%; add 18% GST if PayPal charges it on the fee, e.g. 5.2).') ?>
            </div>
            <div class="col-md-4">
                <?= $form->field($model, 'paypal_fee_fixed_usd')->input('number', ['step' => '0.01', 'min' => 0, 'max' => 5])->hint('PayPal\'s fixed fee per payment (about US$0.30).') ?>
            </div>
        </div>
    <?= $end ?>

    <div class="row">
        <div class="col-xl-6">
            <?= $section('fa-headset', 'Contact details', 'Shown on the home and contact pages and in the footer.') ?>
                <div class="row">
                    <div class="col-sm-6"><?= $form->field($model, 'mobile_1')->textInput(['type' => 'tel', 'placeholder' => 'Digits only'])->label('Main phone') ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'mobile_2')->textInput(['type' => 'tel', 'placeholder' => 'Optional'])->label('Second phone') ?></div>
                </div>
                <?= $form->field($model, 'email_1')->textInput(['placeholder' => 'info@company.com'])->label('Main email')->hint('Also receives the contact-form and audit-request emails.') ?>
                <div class="row">
                    <div class="col-sm-6"><?= $form->field($model, 'email_2')->textInput(['placeholder' => 'Optional'])->label('Second email') ?></div>
                    <div class="col-sm-6"><?= $form->field($model, 'email_3')->textInput(['placeholder' => 'Optional'])->label('Third email')->hint('Kept for reference; not shown anywhere yet.') ?></div>
                </div>
            <?= $end ?>
        </div>
        <div class="col-xl-6">
            <?= $section('fa-map-marker-alt', 'Address', 'Shown on the home and contact pages, the footer and the policy pages.') ?>
                <?= $form->field($model, 'address_line_1')->textInput(['placeholder' => 'Building, floor'])->label('Address line 1') ?>
                <?= $form->field($model, 'address_line_2')->textInput(['placeholder' => 'Street, area'])->label('Address line 2') ?>
                <?= $form->field($model, 'address_line_3')->textInput(['placeholder' => 'City, state, PIN'])->label('Address line 3') ?>
            <?= $end ?>
        </div>
    </div>

    <?= $section('fa-share-alt', 'Social links', 'Icons in the website footer. Leave one empty to hide it.') ?>
        <div class="row">
            <?php foreach (['facebook' => ['fa-facebook-f', 'Facebook'], 'x' => ['fa-twitter', 'X (Twitter)'], 'linkedin' => ['fa-linkedin-in', 'LinkedIn'], 'youtube' => ['fa-youtube', 'YouTube'], 'instagram' => ['fa-instagram', 'Instagram']] as $attribute => [$icon, $name]): ?>
                <div class="col-md-6 col-xl-4">
                    <?= $form->field($model, $attribute, ['inputTemplate' => '<div class="input-group"><span class="input-group-text"><i class="fab ' . $icon . '"></i></span>{input}</div>'])->textInput(['placeholder' => 'https://…'])->label($name) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?= $end ?>

    <div class="settings-save">
        <span class="small text-muted d-none d-sm-inline">Saves every section on this page.</span>
        <?= Html::submitButton('<i class="fas fa-save me-1"></i> Save settings', ['class' => 'btn btn-primary px-4']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
