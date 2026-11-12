<?php

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use common\services\payment\PayPalService;
use common\services\payment\RazorpayService;

/** @var yii\web\View $this */
/** @var common\models\SiteSetting $model */

$this->title = 'Global Systems Branding Settings';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Base Identity Parameters' => 'Agency Corporate Name is shown in the public website\'s header, footer and policy pages. "Check renewal invoices before they are sent" holds new renewal invoices under Invoices (Waiting for review) until you send them.',
    'Payments: who sees which gateway' => 'Decided by the country on the invoice, taken from the client\'s billing profile. India (or no country): Razorpay, if "Razorpay for clients in India" is ticked. Abroad: Razorpay and/or PayPal, whichever of their boxes is ticked.',
    'Payment keys' => 'Gateway keys are never entered here: they stay in the .env file on the server. The badge shows Keys missing, Test keys or Live keys; on the live site customers never get a checkout that uses test keys.',
    'Exchange rate' => 'PayPal charges in US$. With "Use the daily exchange rate" ticked, the European Central Bank rate less the margin is used; otherwise, or if the daily rate stops updating, "Rate by hand" is used. With no rate at all, PayPal is hidden.',
    'PayPal fees' => 'Built into the US$ price the client sees, so you receive about the full rupee amount. The line below them shows what a ₹10,000 invoice costs with PayPal today.',
    'Prices already sent' => 'Once an invoice for a client abroad is emailed, its dollar rate is locked until its due date, so changing the rate here doesn\'t change it. A change to the PayPal fee does apply to it.',
    'Support & Communication Links, Address, Social Profiles' => 'Shown on the public website (home and contact pages, footer). Primary Helpdesk Inbound Email also receives the contact-form and audit-request emails. Phone numbers take digits only. Leave a social link empty to hide its icon.',
    'Commit Changes to Infrastructure Matrix' => 'Saves every section on this page at once.',
];
?>
<div class="setting-index">
    
    <p class="text-muted small">Site name, support contacts, emails, address and social links shown across the site.</p>

    <!-- 📊 ARCHITECTURE ALIGNED INPUT MATRIX -->
    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 0.75rem;">
        <?php $form = ActiveForm::begin([
            'id' => 'site-branding-matrix-form',
            'options' => ['class' => 'needs-validation']
        ]); ?>

        <!-- SECTION 1: SYSTEM IDENTITIES -->
        <h4 class="fw-bold mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">🏢 Base Identity Parameters</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'site_name')->textInput(['class' => 'form-control py-2', 'placeholder' => 'e.g., Miracle Web Technologies']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'site_url')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://miraclewebtechnologies.com']) ?>
            </div>
        </div>

        <div class="mb-3">
            <?= $form->field($model, 'review_invoices')->checkbox()->hint('New renewal invoices wait under Invoices (Waiting for review) until you send them. The customer sees nothing until then. The due date stays the service\'s expiry date.') ?>
        </div>

        <!-- PAYMENTS: which gateway each client sees; keys stay in .env -->
        <?php
        $razorpay = new RazorpayService();
        $paypal = new PayPalService();
        $keyStatus = static fn(bool $hasKeys, bool $test, string $envNames): string => match (true) {
            !$hasKeys => '<span class="badge bg-secondary">Keys missing</span> <span class="small text-muted">Add ' . $envNames . ' to .env on the server.</span>',
            $test && YII_ENV_PROD => '<span class="badge bg-warning text-dark">Test keys</span> <span class="small text-muted">Customers never see a test checkout on the live site.</span>',
            $test => '<span class="badge bg-info text-dark">Test keys</span>',
            default => '<span class="badge bg-success">Live keys</span>',
        };
        $market = (float) $model->usd_market_rate;
        $rateNow = PayPalService::usdRate();
        ?>
        <h4 class="fw-bold mt-4 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">💳 Payments</h4>
        <p class="small text-muted">Choose which online payment customers see, by their billing country. A gateway works only when it is ticked here and its keys are in <code>.env</code>. UPI / bank transfer with proof upload is always offered.</p>
        <div class="row">
            <div class="col-md-6 mb-3">
                <p class="fw-semibold mb-1">Razorpay (charges in ₹) <?= $keyStatus($razorpay->isEnabled(), $razorpay->isTestMode(), 'RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET') ?></p>
                <?= $form->field($model, 'razorpay_india')->checkbox() ?>
                <?= $form->field($model, 'razorpay_abroad')->checkbox()->hint('Foreign cards need International Payments switched on in your Razorpay account.') ?>
            </div>
            <div class="col-md-6 mb-3">
                <p class="fw-semibold mb-1">PayPal (charges in US$) <?= $keyStatus($paypal->isEnabled(), $paypal->isSandbox(), 'PAYPAL_CLIENT_ID and PAYPAL_SECRET') ?></p>
                <?= $form->field($model, 'paypal_abroad')->checkbox()->hint('PayPal India cannot take payments from customers in India, so it is offered to clients abroad only.') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'usd_rate_auto')->checkbox()->hint($market > 0
                    ? 'Today: ₹' . Html::encode(rtrim(rtrim(number_format($market, 4, '.', ''), '0'), '.')) . ' per US$1 (European Central Bank, updated ' . Yii::$app->formatter->asRelativeTime((int) $model->usd_rate_fetched_at) . ').'
                    : 'Fetched free from the European Central Bank (Frankfurter) when a PayPal invoice is opened.') ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'usd_rate_margin')->input('number', ['step' => '0.1', 'min' => 0, 'max' => 20])->hint('PayPal converts dollars to rupees about 3–4% below the market rate. Charging this much more covers it.') ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'usd_inr_rate')->input('number', ['step' => '0.0001', 'min' => 10])->hint('Used when the daily rate is off or could not be fetched.') ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'paypal_fee_percent')->input('number', ['step' => '0.01', 'min' => 0, 'max' => 15])->hint('PayPal\'s fee on payments from abroad (about 4.4%; add 18% GST if PayPal charges it on the fee, e.g. 5.2).') ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'paypal_fee_fixed_usd')->input('number', ['step' => '0.01', 'min' => 0, 'max' => 5])->hint('PayPal\'s fixed fee per payment (about US$0.30).') ?>
            </div>
            <div class="col-md-4 mb-3 small text-muted">
                Both fees are built into the US$ price the client sees (one total, never a separate "PayPal fee"), so you receive about the full rupee amount.
            </div>
        </div>
        <p class="small mb-3">
            <?= $rateNow > 0
                ? 'Now: ₹' . Html::encode(rtrim(rtrim(number_format($rateNow, 4, '.', ''), '0'), '.')) . ' per US$1 before fees. A ₹10,000 invoice is <strong>US$' . PayPalService::chargeFor(10000, $rateNow) . '</strong> with PayPal.'
                : '<span class="text-danger">No exchange rate yet: PayPal stays hidden until one is fetched or set by hand.</span>' ?>
        </p>

        <!-- SECTION 2: COMMUNICATIONS CONNECTORS -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">📞 Support & Communication Links</h4>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'mobile_1')->textInput(['class' => 'form-control py-2', 'type' => 'tel', 'placeholder' => 'Primary Number']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'mobile_2')->textInput(['class' => 'form-control py-2', 'type' => 'tel', 'placeholder' => 'Alternative Number (Optional)']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_1')->textInput(['class' => 'form-control py-2', 'placeholder' => 'info@company.com']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_2')->textInput(['class' => 'form-control py-2', 'placeholder' => 'support@company.com (Optional)']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'email_3')->textInput(['class' => 'form-control py-2', 'placeholder' => 'sales@company.com (Optional)']) ?>
            </div>
        </div>

        <!-- SECTION 3: PHYSICAL LOCATIONS MAP -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">📍 Physical Headquarters Address Blocks</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_1')->textInput(['class' => 'form-control py-2', 'placeholder' => 'Suite, Building Floor']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_2')->textInput(['class' => 'form-control py-2', 'placeholder' => 'Street Area, Complex Path']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'address_line_3')->textInput(['class' => 'form-control py-2', 'placeholder' => 'City, State, Pin Code']) ?>
            </div>
        </div>

        <!-- SECTION 4: SOCIAL MEDIA GRAPH PARAMETERS -->
        <h4 class="fw-bold mt-3 mb-3 text-dark border-bottom pb-2" style="font-size: 1.1rem; color: #0f172a;">🌐 Corporate Social Network Profiles</h4>
        <div class="row">
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'facebook')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://facebook.com...']) ?>
            </div>
            <div class="col-md-4 mb-3">
                <!-- 💡 ALIGNED TO YOUR SCHEMA 'x' COLUMN -->
                <?= $form->field($model, 'x')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://x.com...'])->label('Twitter / X Profile') ?>
            </div>
            <div class="col-md-4 mb-3">
                <?= $form->field($model, 'linkedin')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://linkedin.com...']) ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'youtube')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://youtube.com...']) ?>
            </div>
            <div class="col-md-6 mb-3">
                <?= $form->field($model, 'instagram')->textInput(['class' => 'form-control py-2', 'placeholder' => 'https://instagram.com...']) ?>
            </div>
        </div>

        <!-- SAVE BUTTON CONTAINER -->
        <div class="form-group mt-4 pt-2 text-end">
            <?= Html::submitButton('Commit Changes to Infrastructure Matrix ✓', [
                'class' => 'btn btn-lg fw-bold px-5 text-white shadow-sm',
                'style' => 'background-color: #00a3e0 !important; border: none !important; border-radius: 0.5rem; transition: all 0.2s ease;'
            ]) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
