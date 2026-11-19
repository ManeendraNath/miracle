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
    'Search engines' => 'Google and Bing ask for a code to prove the site is yours. Paste it here and save, then press Verify in their tools. Social links above are also given to search engines as the company\'s profiles.',
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
$rateNow = PayPalService::usdRate();
// As saved, after usdRate() above may just have fetched a new rate ($model may hold a form that failed to save).
$saved = \common\components\CustomYii::getSetting();
$market = (float) $saved->usd_market_rate;
// A daily rate too old to use: the fetch has been failing.
$stale = $rateNow <= 0 && (bool) $saved->usd_rate_auto && $market > 0;

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
                <?= $form->field($model, 'site_name')->textInput(['placeholder' => 'e.g. Sharma Web Services'])->label('Business name')->hint('On invoices, receipts, emails and the website.') ?>
                <?= $form->field($model, 'site_url')->textInput(['placeholder' => 'https://example.com'])->label('Website address')->hint('Linked from the emails to customers.') ?>
                <?= $form->field($model, 'tagline')->textInput(['maxlength' => 150, 'placeholder' => 'e.g. Upgrade Your Business With Us'])->label('Tagline')->hint('Under the name at the top of emails. Empty = none.') ?>
                <?= $form->field($model, 'services_line')->textInput(['maxlength' => 200, 'placeholder' => 'e.g. Hosting • Domains • Maintenance'])->label('What you offer, in one line')->hint('At the foot of emails. Empty = none.') ?>
                <?= $form->field($model, 'founded_year')->textInput(['type' => 'number', 'min' => 1900, 'max' => 2100])->label('Year founded')->hint('Email footers say "© {year}-' . date('Y') . '". Empty = this year only.') ?>
                <?php if (\common\components\PortalOnly::enabled()): ?>
                    <?= $form->field($model, 'terms_url')->textInput(['maxlength' => 255, 'placeholder' => 'https://example.com/terms'])->label('Your terms page')->hint('Linked where customers pay, and from the portal footer. Payment gateways ask for these.') ?>
                    <?= $form->field($model, 'refund_url')->textInput(['maxlength' => 255, 'placeholder' => 'https://example.com/refund-policy'])->label('Your refund policy page') ?>
                    <?= $form->field($model, 'privacy_url')->textInput(['maxlength' => 255, 'placeholder' => 'https://example.com/privacy-policy'])->label('Your privacy policy page') ?>
                <?php endif; ?>
                <?= $form->field($model, 'grievance_name')->textInput(['maxlength' => 100])->label('Grievance Officer')->hint('Named in the privacy policy, as Indian IT rules require.') ?>
                <?= $form->field($model, 'grievance_email')->textInput(['maxlength' => 255])->label('Grievance Officer email')->hint('One or more addresses, separated by commas.') ?>
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
                <?= match (true) {
                    $rateNow > 0 => 'Now: <strong>₹' . $rate($rateNow) . ' per US$1</strong> before fees. A ₹10,000 invoice is <strong>US$' . PayPalService::chargeFor(10000, $rateNow) . '</strong> with PayPal.',
                    $stale => 'The daily exchange rate has not updated since ' . Yii::$app->formatter->asDate((int) $saved->usd_rate_fetched_at, 'medium')
                        . ' (last tried ' . Yii::$app->formatter->asRelativeTime((int) $saved->usd_rate_checked_at) . '). PayPal stays hidden until it updates, or until you enter a rate by hand below.',
                    default => 'No exchange rate yet: PayPal stays hidden until one is fetched or set by hand.',
                } ?>
                <?php if ($rateNow <= 0 && (bool) $saved->usd_rate_auto): ?>
                    <div class="mt-2"><?= Html::submitButton('Fetch the rate now', ['form' => 'fetch-rate-form', 'class' => 'btn btn-sm btn-outline-danger']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <h6 class="settings-subhead">Exchange rate</h6>
        <div class="row">
            <div class="col-md-4">
                <?= $form->field($model, 'usd_rate_auto')->checkbox(['switch' => true])->hint($market > 0
                    ? 'Last fetched: ₹' . $rate($market) . ' per US$1 (European Central Bank), ' . Yii::$app->formatter->asRelativeTime((int) $saved->usd_rate_fetched_at) . '.'
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
                <?= $form->field($model, 'whatsapp')->textInput(['type' => 'tel', 'placeholder' => 'Digits with country code, e.g. 919876543210'])->hint('The green chat button on every public page opens WhatsApp with this number. Leave empty to hide the button.') ?>
                <?= $form->field($model, 'tawk_chat', ['enableClientValidation' => false])->textarea(['rows' => 2, 'placeholder' => 'https://tawk.to/chat/… or the whole widget code'])->hint('The live chat bubble, bottom left on every public page. In Tawk.to: Administration → Chat Widget, copy the Direct Chat Link (or the widget code) and paste it here. Leave empty to hide the chat.') ?>
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
            <?php
            // X: the same logo as the website footer (Font Awesome 5 has only the old Twitter bird).
            $xLogo = $this->renderFile('@frontend/views/layouts/_x-logo.php');
            $icons = ['facebook' => ['<i class="fab fa-facebook-f"></i>', 'Facebook'], 'x' => [$xLogo, 'X'], 'linkedin' => ['<i class="fab fa-linkedin-in"></i>', 'LinkedIn'], 'youtube' => ['<i class="fab fa-youtube"></i>', 'YouTube'], 'instagram' => ['<i class="fab fa-instagram"></i>', 'Instagram']];
            ?>
            <?php foreach ($icons as $attribute => [$icon, $name]): ?>
                <div class="col-md-6 col-xl-4">
                    <?= $form->field($model, $attribute, ['inputTemplate' => '<div class="input-group"><span class="input-group-text" aria-hidden="true">' . $icon . '</span>{input}</div>'])->textInput(['placeholder' => 'https://…'])->label($name) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?= $end ?>

    <?= $section('fa-search', 'Search engines', 'Proves to Google and Bing that this website is yours, for their webmaster tools.') ?>
        <div class="row">
            <div class="col-md-6"><?= $form->field($model, 'google_site_verification', ['enableClientValidation' => false])->textInput(['placeholder' => 'e.g. aBcD1234…'])->hint('Search Console → Add property → URL prefix → HTML tag. Paste the code or the whole tag.') ?></div>
            <div class="col-md-6"><?= $form->field($model, 'bing_site_verification', ['enableClientValidation' => false])->textInput(['placeholder' => 'e.g. 1A2B3C…'])->hint('Bing Webmaster Tools → Add site → HTML Meta Tag. Paste the code or the whole tag.') ?></div>
        </div>
    <?= $end ?>

    <div class="settings-save">
        <span class="small text-muted d-none d-sm-inline">Saves every section on this page.</span>
        <?= Html::submitButton('<i class="fas fa-save me-1"></i> Save settings', ['class' => 'btn btn-primary px-4']) ?>
    </div>

    <?php ActiveForm::end(); ?>
    <?php // Its own form: the button sits inside the settings form above, which it must not submit. ?>
    <?= Html::beginForm(['fetch-rate'], 'post', ['id' => 'fetch-rate-form']) . Html::endForm() ?>
</div>
