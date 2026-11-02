<?php

declare(strict_types=1);

use common\models\Invoice;
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
$formatter = Yii::$app->formatter;
$inr = static fn($amount): string => $formatter->asCurrency($amount, 'INR');
// Same tax breakdown as the PDF (common/mail/invoice-pdf.php).
$taxable = max(0, round((float) $model->subtotal_amount - (float) $model->discount_amount, 2));
$taxes = array_filter([
    'CGST' => (float) $model->cgst_percent,
    'SGST' => (float) $model->sgst_percent,
    'IGST' => (float) $model->igst_percent,
]);
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
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-0 d-flex align-items-center gap-2 flex-wrap">
            <?= Html::encode($model->invoice_number) ?>
            <span class="badge fs-6 fw-semibold <?= $statusClass ?>"><?= Html::encode(Invoice::getStatuses()[$model->status] ?? $model->status) ?></span>
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
                        <?php foreach ([$model->client_address_line_1, $model->client_address_line_2, implode(', ', array_filter([$model->client_city, $model->client_state, $model->client_postal_code]))] as $line): ?>
                            <?php if ($line): ?>
                                <div class="text-body-secondary"><?= Html::encode($line) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($model->client_gstin): ?>
                            <div class="small text-body-secondary">GSTIN: <?= Html::encode($model->client_gstin) ?></div>
                        <?php endif; ?>
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
                            <?php foreach ($taxes as $name => $percent): ?>
                                <tr>
                                    <td class="text-body-secondary"><?= $name ?> (<?= rtrim(rtrim(number_format($percent, 2), '0'), '.') ?>%)</td>
                                    <td class="text-end"><?= $inr(round($taxable * $percent / 100, 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-top">
                                <td class="fw-bold pt-2">Total</td>
                                <td class="text-end fw-bold pt-2"><?= $inr($model->total_payable) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 order-1 order-xl-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="small text-body-secondary">Amount due</div>
                <div class="display-6 fw-bold mb-3 <?= $outstandingAmount > 0 ? '' : 'text-success' ?>"><?= $inr($outstandingAmount) ?></div>
                <dl class="row small mb-0">
                    <dt class="col-6 fw-normal text-body-secondary">Invoice total</dt>
                    <dd class="col-6 text-end mb-1"><?= $inr($model->total_payable) ?></dd>
                    <dt class="col-6 fw-normal text-body-secondary">Received</dt>
                    <dd class="col-6 text-end mb-0"><?= $inr($capturedAmount) ?></dd>
                </dl>

                <?php if ($pendingPayment !== null): ?>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="fas fa-hourglass-half me-1"></i>
                        <strong>Payment under review.</strong>
                        We'll confirm it shortly. Reference: <?= Html::encode($pendingPayment->gateway_payment_id) ?>
                    </div>
                <?php elseif ($isPayable): ?>
                    <div class="d-grid gap-2 mt-3">
                        <?php if ($canPayOnline): ?>
                            <button type="button" id="pay-online" class="btn btn-success btn-lg"
                                    data-order-url="<?= Url::to(['pay-online', 'number' => $model->invoice_number]) ?>">
                                <i class="fas fa-lock me-1"></i> Pay <?= $inr($outstandingAmount) ?> online
                            </button>
                        <?php endif; ?>
                        <?= Html::a(
                            '<i class="fas fa-university me-1"></i> Paid by UPI / bank transfer? Submit proof',
                            ['submit-payment', 'number' => $model->invoice_number],
                            ['class' => 'btn ' . ($canPayOnline ? 'btn-outline-secondary btn-sm' : 'btn-success')]
                        ) ?>
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
