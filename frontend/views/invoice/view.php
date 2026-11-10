<?php

declare(strict_types=1);

use common\models\Invoice;
use common\services\billing\TaxCalculator;
use common\services\payment\PayPalService;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$this->title = 'Invoice ' . $model->invoice_number;

$this->params['breadcrumbs'][] = [
    'label' => 'My Invoices',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $model->invoice_number;

$isPayable = in_array($model->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) && $outstandingAmount > 0;
$canPayOnline = $isPayable && $pendingPayment === null && !empty($razorpayEnabled);
// Clients abroad pay in US dollars with PayPal (rate from Site Settings).
$canPayPal = $isPayable && $pendingPayment === null && !empty($paypal);
$formatter = Yii::$app->formatter;
$inr = static fn($amount): string => $formatter->asCurrency($amount, 'INR');
// Same tax breakdown as the PDF and the GST report.
$tax = (new TaxCalculator())->forInvoice($model);
$taxes = array_filter([
    'CGST' => [(float) $model->cgst_percent, $tax['cgstAmount']],
    'SGST' => [(float) $model->sgst_percent, $tax['sgstAmount']],
    'IGST' => [(float) $model->igst_percent, $tax['igstAmount']],
], static fn(array $t): bool => $t[0] > 0);
$isOverdue = $isPayable && $model->due_date && strtotime((string) $model->due_date) < strtotime('today');
$statusClass = match ($model->status) {
    Invoice::STATUS_PAID => 'bg-success-subtle text-success-emphasis',
    Invoice::STATUS_PARTIALLY_PAID => 'bg-warning-subtle text-warning-emphasis',
    Invoice::STATUS_UNPAID => 'bg-danger-subtle text-danger-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};

if ($canPayOnline) {
    $this->registerJsFile('https://checkout.razorpay.com/v1/checkout.js');
    $callbackUrl = Json::htmlEncode(Url::to(['razorpay-callback', 'number' => $model->invoice_number]));
    $this->registerJs(<<<JS
document.getElementById('pay-online').addEventListener('click', function () {
    var button = this;
    button.disabled = true;
    fetch(button.dataset.orderUrl, {
        method: 'POST',
        headers: {'X-CSRF-Token': yii.getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest'}
    })
        .then(function (r) { return r.json().then(function (data) { return {ok: r.ok, data: data}; }); })
        .then(function (res) {
            if (!res.ok) { throw new Error(res.data.error || 'Could not start online payment.'); }
            var options = res.data;
            options.handler = function (payment) {
                var form = document.createElement('form');
                form.method = 'post';
                form.action = {$callbackUrl};
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
            options.modal = {ondismiss: function () { button.disabled = false; }};
            var checkout = new Razorpay(options);
            checkout.on('payment.failed', function (r) {
                alert('Payment failed: ' + (r.error && r.error.description ? r.error.description : 'please try again.'));
                button.disabled = false;
            });
            checkout.open();
        })
        .catch(function (e) { alert(e.message); button.disabled = false; });
});
JS);
}

if ($canPayPal) {
    $this->registerJsFile('https://www.paypal.com/sdk/js?' . http_build_query(['client-id' => $paypal['clientId'], 'currency' => 'USD', 'intent' => 'capture']));
    $this->registerJs(<<<JS
(function () {
    var box = document.getElementById('paypal-buttons');
    var headers = {'X-CSRF-Token': yii.getCsrfToken(), 'X-Requested-With': 'XMLHttpRequest'};
    paypal.Buttons({
        createOrder: function () {
            return fetch(box.dataset.orderUrl, {method: 'POST', headers: headers})
                .then(function (r) { return r.json().then(function (data) { return {ok: r.ok, data: data}; }); })
                .then(function (res) {
                    if (!res.ok) { alert(res.data.error || 'Could not start PayPal payment.'); throw new Error(res.data.error); }
                    return res.data.id;
                });
        },
        onApprove: function (data) {
            box.innerHTML = '<p class="text-center my-3">Confirming your payment…</p>';
            var body = new URLSearchParams();
            body.append('order_id', data.orderID);
            return fetch(box.dataset.captureUrl, {method: 'POST', headers: headers, body: body})
                .finally(function () { window.location.reload(); });
        }
    }).render('#paypal-buttons');
})();
JS);
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2 flex-wrap">
            <?= Html::encode($model->invoice_number) ?>
            <span class="badge fs-6 fw-semibold <?= $statusClass ?>"><?= Html::encode($model->statusLabel()) ?></span>
            <?php if ($isOverdue): ?>
                <span class="badge fs-6 fw-semibold bg-danger">Overdue</span>
            <?php endif; ?>
        </h1>
    </div>
    <?= Html::a('<i class="fas fa-download me-1"></i> Download PDF', ['download', 'number' => $model->invoice_number], ['class' => 'btn btn-outline-primary']) ?>
</div>

<div class="row g-4">
    <div class="col-xl-8 order-2 order-xl-1">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <div class="small text-uppercase text-body-secondary fw-semibold mb-1">Billed to</div>
                        <div class="fw-semibold"><?= Html::encode($model->client_name) ?></div>
                        <?php foreach ([$model->client_address_line_1, $model->client_address_line_2, $model->getClientCityLine()] as $line): ?>
                            <?php if ($line): ?>
                                <div class="text-body-secondary"><?= Html::encode($line) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php foreach ($model->getClientTaxLines() as $line): ?>
                            <div class="small text-body-secondary"><?= Html::encode($line) ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-sm-end gap-4">
                            <div>
                                <div class="small text-uppercase text-body-secondary fw-semibold mb-1">Issued</div>
                                <div class="text-nowrap"><?= $formatter->asDate($model->created_at, 'php:d M Y') ?></div>
                            </div>
                            <div>
                                <div class="small text-uppercase text-body-secondary fw-semibold mb-1">Due</div>
                                <div class="text-nowrap <?= $isOverdue ? 'text-danger fw-semibold' : '' ?>"><?= $model->due_date ? $formatter->asDate($model->due_date, 'php:d M Y') : '-' ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="small text-body-secondary">
                            <tr>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end d-none d-sm-table-cell">Unit price</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-break"><?= Html::encode($item->description) ?></div>
                                        <div class="small text-body-secondary"><?= Html::encode($item->category) ?></div>
                                    </td>
                                    <td class="text-end"><?= (int) $item->quantity ?></td>
                                    <td class="text-end text-nowrap d-none d-sm-table-cell"><?= $inr($item->unit_price) ?></td>
                                    <td class="text-end text-nowrap"><?= $inr($item->total_price) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row justify-content-end mt-3">
                    <div class="col-sm-7 col-lg-6">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-body-secondary">Subtotal</td>
                                <td class="text-end"><?= $inr($model->subtotal_amount) ?></td>
                            </tr>
                            <?php if ((float) $model->discount_amount > 0): ?>
                                <tr>
                                    <td class="text-body-secondary">Discount<?= $model->coupon_code ? ' (' . Html::encode($model->coupon_code) . ')' : '' ?></td>
                                    <td class="text-end text-success">-<?= $inr($model->discount_amount) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($taxes as $name => [$percent, $amount]): ?>
                                <tr>
                                    <td class="text-body-secondary"><?= $name ?> (<?= rtrim(rtrim(number_format($percent, 2), '0'), '.') ?>%)</td>
                                    <td class="text-end"><?= $inr($amount) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-top">
                                <td class="fw-bold pt-2">Total</td>
                                <td class="text-end fw-bold pt-2"><?= $inr($model->total_payable) ?></td>
                            </tr>
                        </table>
                    </div>
                    <?php if ($model->getExportNote() !== null): ?>
                        <div class="col-12 small text-body-secondary mt-2"><?= Html::encode($model->getExportNote()) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 order-1 order-xl-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="small text-body-secondary">Amount due</div>
                <div class="display-6 fw-bold mb-3 <?= $outstandingAmount > 0 || $model->status === Invoice::STATUS_CARRIED_FORWARD ? '' : 'text-success' ?>"><?= $inr($outstandingAmount) ?></div>
                <dl class="row small mb-0">
                    <dt class="col-6 fw-normal text-body-secondary">Invoice total</dt>
                    <dd class="col-6 text-end mb-1"><?= $inr($model->total_payable) ?></dd>
                    <dt class="col-6 fw-normal text-body-secondary">Received</dt>
                    <dd class="col-6 text-end mb-0"><?= $inr($capturedAmount) ?></dd>
                </dl>

                <?php if (!empty($canChangeCoupon)): ?>
                    <div class="border-top pt-3 mt-3">
                        <?php if ($model->coupon_code): ?>
                            <?= Html::beginForm(['remove-coupon', 'number' => $model->invoice_number], 'post', ['class' => 'd-flex justify-content-between align-items-center gap-2 small']) ?>
                                <span>
                                    <i class="fas fa-tag text-success me-1"></i>
                                    <span class="fw-semibold"><?= Html::encode($model->coupon_code) ?></span>
                                    <span class="text-success">-<?= $inr($model->discount_amount) ?></span>
                                </span>
                                <?= Html::submitButton('Remove', ['class' => 'btn btn-link btn-sm p-0 text-danger']) ?>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <?= Html::beginForm(['apply-coupon', 'number' => $model->invoice_number], 'post') ?>
                                <label for="coupon-code" class="form-label small text-body-secondary mb-1">Have a coupon?</label>
                                <div class="input-group input-group-sm">
                                    <?= Html::textInput('coupon_code', '', ['id' => 'coupon-code', 'class' => 'form-control text-uppercase', 'maxlength' => 32, 'required' => true, 'autocomplete' => 'off', 'placeholder' => 'Coupon code']) ?>
                                    <?= Html::submitButton('Apply', ['class' => 'btn btn-outline-primary']) ?>
                                </div>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($pendingPayment !== null): ?>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="fas fa-hourglass-half me-1"></i>
                        <strong>Payment under review.</strong>
                        We'll confirm it shortly.<?= (string) $pendingPayment->gateway_payment_id !== '' ? ' Reference: ' . Html::encode($pendingPayment->gateway_payment_id) : '' ?>
                    </div>
                <?php elseif ($isPayable): ?>
                    <div class="d-grid gap-2 mt-3">
                        <?php if ($canPayPal): ?>
                            <?php $usd = PayPalService::chargeFor((float) $outstandingAmount, (float) $paypal['rate']); ?>
                            <p class="small text-center mb-0">
                                Pay <strong>US$<?= Html::encode($usd) ?></strong> with PayPal or a card
                                for the <?= $inr($outstandingAmount) ?> due.
                                <?php if (!empty($paypal['until'])): ?>
                                    <br><span class="text-body-secondary">This price holds until <?= Html::encode(Yii::$app->formatter->asDate($paypal['until'], 'php:d M Y')) ?>.</span>
                                <?php endif; ?>
                            </p>
                            <div id="paypal-buttons"
                                 data-order-url="<?= Url::to(['paypal-order', 'number' => $model->invoice_number]) ?>"
                                 data-capture-url="<?= Url::to(['paypal-capture', 'number' => $model->invoice_number]) ?>"></div>
                        <?php endif; ?>
                        <?php if ($canPayOnline): ?>
                            <button type="button" id="pay-online" class="btn btn-success btn-lg"
                                    data-order-url="<?= Url::to(['pay-online', 'number' => $model->invoice_number]) ?>">
                                <i class="fas fa-lock me-1"></i> Pay <?= $inr($outstandingAmount) ?> online
                            </button>
                        <?php endif; ?>
                        <?= Html::a(
                            '<i class="fas fa-university me-1"></i> Paid by UPI / bank transfer? Submit proof',
                            ['submit-payment', 'number' => $model->invoice_number],
                            ['class' => 'btn ' . ($canPayOnline || $canPayPal ? 'btn-outline-secondary btn-sm' : 'btn-success')]
                        ) ?>
                    </div>
                    <?php if ($canPayOnline && !$canPayPal): ?>
                        <p class="small text-body-secondary text-center mt-2 mb-0">
                            Paying from outside India? <?= Html::a('Raise a support ticket', ['/support/create', 'subject' => 'Paying from outside India']) ?> and we'll confirm how to pay.
                        </p>
                    <?php endif; ?>
                    <p class="small text-body-secondary text-center mt-2 mb-0">
                        By paying you agree to our <?= Html::a('Terms &amp; Conditions', ['/site/terms'], ['target' => '_blank']) ?>
                        and <?= Html::a('Refund &amp; Cancellation Policy', ['/site/refund-policy'], ['target' => '_blank']) ?>.
                    </p>
                <?php elseif ($model->status === Invoice::STATUS_CARRIED_FORWARD): ?>
                    <div class="alert alert-info small mt-3 mb-0">
                        <i class="fas fa-share me-1"></i>
                        <?php if ($carriedInto !== null): ?>
                            The unpaid balance moved to invoice <?= Html::a(Html::encode($carriedInto->invoice_number), ['view', 'number' => $carriedInto->invoice_number]) ?>. Please pay it there.
                        <?php else: ?>
                            <?php $next = $model->carriedTo(); ?>
                            <?php if ($next !== null && $next->status === Invoice::STATUS_DRAFT): ?>
                                The unpaid balance moved to your next invoice, which we will send you shortly. Please pay it there.
                            <?php else: ?>
                                The unpaid balance moved to <?= $next !== null ? 'invoice ' . Html::a(Html::encode($next->invoice_number), ['view', 'number' => $next->invoice_number]) . ', which is settled' : 'a later invoice' ?>. Nothing more is owed here.
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php elseif ($model->status === Invoice::STATUS_PAID): ?>
                    <div class="alert alert-success small mt-3 mb-0">
                        <i class="fas fa-check-circle me-1"></i> Paid in full. Thank you!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
