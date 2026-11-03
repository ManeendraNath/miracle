<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var bool $ready */
/** @var string $keyId */
/** @var float $amount */
/** @var array{id: string, amount: int}|null $order */

$this->title = 'Razorpay Test';
$this->params['breadcrumbs'][] = $this->title;
$this->params['subtitle'] = 'Try a payment with test keys. No invoice, customer or transaction is created or changed.';
?>

<div class="razorpay-test-index">
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header"><h3 class="card-title">Test payment</h3></div>
                <div class="card-body">
                    <?php if (!$ready): ?>
                        <div class="alert alert-warning mb-0">
                            Needs <code>RAZORPAY_ENABLED=true</code> and <strong>test</strong> keys
                            (<code>rzp_test_…</code>) in <code>.env</code>. This page never runs with live keys.
                        </div>
                    <?php else: ?>
                        <p class="text-muted">Test mode key <code><?= Html::encode(substr($keyId, 0, 13)) ?>…</code>. No real money moves.</p>
                        <?= Html::beginForm(['index'], 'post') ?>
                        <div class="form-group">
                            <?= Html::label('Amount (₹)', 'amount') ?>
                            <?= Html::input('number', 'amount', number_format($amount, 2, '.', ''), ['id' => 'amount', 'class' => 'form-control', 'min' => 1, 'step' => '0.01', 'required' => true]) ?>
                        </div>
                        <?= Html::submitButton('<i class="fas fa-credit-card mr-1"></i> Create test order and pay', ['class' => 'btn btn-primary']) ?>
                        <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header"><h3 class="card-title">Test details</h3></div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>UPI success: <code>success@razorpay</code></li>
                        <li>UPI failure: <code>failure@razorpay</code></li>
                        <li>Cards: use a test card from Razorpay's docs (Test Card Details); any future expiry, any CVV.</li>
                        <li>Netbanking: pick any bank, then choose Success or Failure on Razorpay's test page.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if ($order !== null) {
    $this->registerJsFile('https://checkout.razorpay.com/v1/checkout.js');
    $options = Json::htmlEncode([
        'key' => $keyId,
        'order_id' => $order['id'],
        'amount' => $order['amount'],
        'currency' => 'INR',
        'name' => Yii::$app->name . ' (test)',
        'description' => 'Gateway test payment',
    ]);
    $verifyUrl = Json::htmlEncode(Url::to(['verify']));
    $this->registerJs(<<<JS
(function () {
    var options = {$options};
    options.handler = function (payment) {
        var form = document.createElement('form');
        form.method = 'post';
        form.action = {$verifyUrl};
        var fields = {
            razorpay_order_id: payment.razorpay_order_id,
            razorpay_payment_id: payment.razorpay_payment_id,
            razorpay_signature: payment.razorpay_signature
        };
        fields[yii.getCsrfParam()] = yii.getCsrfToken();
        Object.keys(fields).forEach(function (name) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = name; input.value = fields[name];
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    };
    var checkout = new Razorpay(options);
    checkout.on('payment.failed', function (r) {
        alert('Payment failed (as expected for a failure test?): ' + (r.error && r.error.description ? r.error.description : 'unknown error'));
    });
    checkout.open();
})();
JS);
}
