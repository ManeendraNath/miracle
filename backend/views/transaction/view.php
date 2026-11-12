<?php

use common\models\Invoice;
use common\models\Transaction;
use common\services\payment\RazorpayService;
use yii\bootstrap5\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var common\models\Transaction $model */
$this->title = 'Transaction Audit: #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
$this->params['actions'] = [Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-light border'])];
if ($model->status === Transaction::STATUS_PENDING && !$model->isOnline()) {
    $this->params['actions'][] = Html::a('<i class="fas fa-check-circle me-1"></i> Approve Payment', ['approve', 'id' => $model->id], [
        'class' => 'btn btn-success',
        'data' => [
            'confirm' => 'Confirm this payment after verifying the bank / UPI transaction?',
            'method' => 'post',
        ],
    ]);
    $this->params['actions'][] = Html::a('<i class="fas fa-times-circle me-1"></i> Reject / Fail', ['reject', 'id' => $model->id], [
        'class' => 'btn btn-danger',
        'data' => [
            'confirm' => 'Reject this submitted payment proof?',
            'method' => 'post',
        ],
    ]);
}
if ($model->status === Transaction::STATUS_PENDING && $model->isOnline()) {
    $gateway = (int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY ? 'Razorpay' : 'PayPal';
    $this->params['actions'][] = Html::a('<i class="fas fa-sync me-1"></i> Check with ' . $gateway, ['check-gateway', 'id' => $model->id], [
        'class' => 'btn btn-outline-primary',
        'data' => ['method' => 'post'],
    ]);
    // Not while PayPal holds the money (a capture id): it may still complete.
    $this->params['actions'][] = (string) $model->gateway_payment_id !== '' ? '' : Html::a('<i class="fas fa-ban me-1"></i> Close as not paid', ['close-online', 'id' => $model->id], [
        'class' => 'btn btn-outline-danger',
        'data' => [
            'confirm' => 'Close this ' . $gateway . ' payment as not paid? ' . $gateway . ' is checked first, and if the money arrives later it is still recorded.',
            'method' => 'post',
        ],
    ]);
    // Only right after a check with the gateway failed for this payment (TransactionController::actionCloseOnline).
    if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id === '' && Yii::$app->session->get('close-unchecked') === (int) $model->id) {
        $this->params['actions'][] = Html::a('<i class="fas fa-ban me-1"></i> Close without checking', ['close-online', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Close this payment without asking Razorpay? Check in the Razorpay dashboard first that no money was taken. If it arrives later it is still recorded.',
                'method' => 'post',
                'params' => ['skip_check' => 1],
            ],
        ]);
    }
}
$refundable = round((float) $model->amount - (float) $model->refunded_amount, 2);
// Razorpay payments are refunded through Razorpay by the form itself; others are returned by hand first.
$viaRazorpay = (int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && (new RazorpayService())->isEnabled();
$refundStep = match (true) {
    $viaRazorpay => 'The amount is refunded to the customer through Razorpay now (5-7 working days to reach them).',
    (int) $model->gateway_id === Transaction::GATEWAY_PAYPAL => 'Refund it in your PayPal account first (Activity, the payment, Issue a refund), then record the rupee amount here.',
    default => 'Only do this after the money has been returned (bank / UPI).',
};
$this->params['help'] = [
    'Invoice' => 'Click the invoice number in the table to open the invoice this payment belongs to.',
];
if ($model->status === Transaction::STATUS_PENDING && !$model->isOnline()) {
    $this->params['help']['Approve Payment'] = 'First check the money really arrived (match the UTR / reference and amount). It then counts on the invoice and the customer gets a confirmation email. If the invoice was already paid or closed, it is kept as an unapplied payment instead.';
    $this->params['help']['Reject / Fail'] = 'For a proof you can\'t match to real money. The payment is marked Failed, and a customer who uploaded a proof is emailed to submit it again.';
    if (!empty($model->payment_receipt_file)) {
        $this->params['help']['View Receipt'] = 'Opens the screenshot or file the customer uploaded as proof, in a new tab.';
    }
}
if ($model->status === Transaction::STATUS_PENDING && $model->isOnline()) {
    $this->params['help']['Pending online payment'] = 'The customer started paying by ' . $gateway . ' but no payment is confirmed yet. Payments are normally recorded automatically.';
    $this->params['help']['Check with ' . $gateway] = 'Asks ' . $gateway . ' about this order now. If it was paid, the payment is recorded; if it was declined, it is marked Failed; otherwise it stays Pending.';
    if ((string) $model->gateway_payment_id === '') {
        $this->params['help']['Close as not paid'] = 'For a customer who gave up paying. ' . $gateway . ' is checked first, then the order is marked Failed. If the money arrives later it is still recorded.';
    } elseif ((int) $model->gateway_id === Transaction::GATEWAY_PAYPAL) {
        $this->params['help'][] = 'PayPal is holding this payment, so it can\'t be closed. It is recorded when PayPal completes it, or marked Failed if PayPal declines it.';
    }
    if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id === '') {
        $this->params['help']['Close without checking'] = 'Appears only right after Razorpay answers that it doesn\'t know this order (for example it was made with other keys). Check in the Razorpay dashboard that no money was taken first. If money arrives later it is still recorded.';
    }
}
if ($model->status === Transaction::STATUS_CAPTURED && $model->isUnapplied()) {
    $this->params['help']['Unapplied payment'] = 'This money arrived after its invoice was settled or closed, so it counts on no invoice. Pick an unpaid invoice of the same customer and click Apply to invoice, or refund it.';
}
if ($model->status === Transaction::STATUS_CAPTURED && $refundable > 0) {
    // Same condition as the refund buttons below.
    if (in_array($model->invoice->status ?? null, \common\services\billing\InvoicePaymentService::CLOSED_STATUSES, true)) {
        $this->params['help']['Refund'] = $refundStep . ' The customer is emailed. The invoice is closed and stays as it is.';
        $carrying = ($model->invoice->status ?? null) === Invoice::STATUS_CARRIED_FORWARD && !$model->isUnapplied() ? (new \common\services\billing\InvoicePaymentService())->openInvoiceCarrying((int) $model->invoice_id) : null;
        if ($carrying !== null && (int) $model->gateway_id !== Transaction::GATEWAY_PAYPAL) {
            $this->params['help']['Refund'] = 'Not yet: this invoice\'s balance moved to ' . $carrying->invoice_number . ', which is still open. The refund can\'t be recorded until that invoice is paid or written off (unless Razorpay already shows the refund), so don\'t return the money before then.';
        }
    } elseif ($model->isUnapplied()) {
        $this->params['help']['Refund (Still Owed)'] = $refundStep . ' Use this one: this payment was not counted on the invoice, so its balance does not change. The customer is emailed.';
        $this->params['help']['Refund & Close Invoice'] = 'Not for this payment: it would close the whole invoice as Refunded, and is refused while the invoice\'s own payments are kept.';
    } else {
        $this->params['help']['Refund (Still Owed)'] = $refundStep . ' For example a wrong payment was returned: the refunded amount becomes due again on the invoice. The customer is emailed.';
        $this->params['help']['Refund & Close Invoice'] = 'Nothing more is owed: the invoice is closed as Refunded and reminders stop. Not possible while other payments on the invoice are kept; use Write Off on the invoice then. An unpaid renewal on it is billed again in full, with no credit for money kept, unless the service is disabled: by the next billing run if the service is Active, or once a suspended or overdue service is set Active again. Renewals already given are not undone, so disable the service if needed.';
    }
    $this->params['help'][] = 'Enter a smaller amount for a part refund.';
}
?>
<div class="transaction-view container-fluid pt-3">
    <div class="mb-4">
        <?php if ($model->status === Transaction::STATUS_PENDING && !$model->isOnline() && !empty($model->payment_receipt_file)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <strong>Payment Proof</strong>
                </div>
                <div class="card-body">
                    <?= Html::a('View Receipt', ['receipt', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']) ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="card card-outline card-primary mb-8">
            <div class="card-header">
                <h3 class="card-title">Invoice Information</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Invoice Number</strong><br>
                        <?= Html::encode($model->invoice->invoice_number) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Client</strong><br>
                        <?= Html::encode($model->invoice->client_name) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Payable</strong><br>
                        <?= Yii::$app->formatter->asCurrency($model->invoice->total_payable, 'INR') ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Status</strong><br>
                        <?= Html::encode(Invoice::labelOf($model->invoice)) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-0">
                    <?=
                    DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-striped table-bordered detail-view mb-0 align-middle'],
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'invoice_id',
                                'label' => 'Invoice',
                                'format' => 'raw',
                                'value' => static function (Transaction $model): string {
                                    return Html::a(Html::encode($model->invoice->invoice_number), ['invoice/view', 'number' => $model->invoice->invoice_number], ['class' => 'fw-semibold']);
                                },
                            ],
                            [
                                'attribute' => 'gateway_id',
                                'label' => 'Payment Method',
                                'value' => $model->gatewayLabel,
                            ],
                            [
                                'attribute' => 'gateway_payment_id',
                                'label' => 'UTR / Transaction Reference No',
                                'options' => ['class' => 'fw-bold text-dark'],
                            ],
                            [
                                'attribute' => 'amount',
                                'value' => Yii::$app->formatter->asCurrency($model->amount, 'INR'),
                            ],
                            [
                                'label' => (int) $model->gateway_id === Transaction::GATEWAY_PAYPAL ? 'PayPal Fee (at the payment\'s rate)' : 'Razorpay Fee (incl. GST)',
                                'value' => Yii::$app->formatter->asCurrency($model->gateway_fee, 'INR') . ' (you receive ' . Yii::$app->formatter->asCurrency((float) $model->amount - (float) $model->gateway_fee, 'INR') . ')',
                                'visible' => (float) $model->gateway_fee > 0,
                            ],
                            [
                                'attribute' => 'refunded_amount',
                                'value' => Yii::$app->formatter->asCurrency($model->refunded_amount, 'INR'),
                                'visible' => (float) $model->refunded_amount > 0,
                            ],
                            [
                                'attribute' => 'status',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    $class = match ($model->status) {
                                        Transaction::STATUS_CAPTURED => 'bg-success',
                                        Transaction::STATUS_PENDING => 'bg-warning text-dark',
                                        Transaction::STATUS_AUTHORIZED => 'bg-info text-dark',
                                        Transaction::STATUS_FAILED => 'bg-danger',
                                        Transaction::STATUS_REFUNDED => 'bg-dark',
                                        default => 'bg-secondary',
                                    };
                                    return Html::tag('span', Html::encode(Transaction::optsStatus()[$model->status] ?? $model->status), ['class' => "badge $class px-3 py-2 rounded-pill"]);
                                }
                            ],
                            'user_payment_notes:ntext',
                            [
                                'attribute' => 'paid_at',
                                'value' => $model->paid_at ? Yii::$app->formatter->asDatetime($model->paid_at, 'php:d M Y, h:i A') : 'Not Captured',
                            ],
                            [
                                'attribute' => 'created_at',
                                'format' => ['datetime', 'php:d M Y, h:i A'],
                            ],
                        ],
                    ])
                    ?>
                </div>
            </div>
        </div>
        <?php if ($model->status === Transaction::STATUS_CAPTURED && $model->isUnapplied()): ?>
            <?php
            $payableInvoices = Invoice::find()->select('invoice_number')
                ->where(['user_id' => (int) $model->invoice->user_id, 'status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])
                ->orderBy(['id' => SORT_DESC])->column();
            ?>
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-header bg-warning-subtle fw-semibold"><i class="fas fa-exchange-alt me-1"></i> Unapplied payment</div>
                    <div class="card-body">
                        <p class="small">This payment arrived after invoice <?= Html::encode($model->invoice->invoice_number) ?> was already settled or closed, so it isn't counted anywhere. Refund it, or apply it to an unpaid invoice of this customer.</p>
                        <?php if ($payableInvoices !== []): ?>
                            <?= Html::beginForm(['apply', 'id' => $model->id], 'post') ?>
                            <?= Html::dropDownList('target_invoice', null, array_combine($payableInvoices, $payableInvoices), ['class' => 'form-select mb-2', 'aria-label' => 'Invoice to apply it to']) ?>
                            <?= Html::submitButton('Apply to invoice', ['class' => 'btn btn-warning w-100', 'data-confirm' => 'Count this payment towards the selected invoice?']) ?>
                            <?= Html::endForm() ?>
                        <?php else: ?>
                            <p class="small text-body-secondary mb-0">The customer has no unpaid invoice to apply it to.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($model->status === Transaction::STATUS_CAPTURED && $refundable > 0): ?>
            <?php
            $invoiceClosed = in_array($model->invoice->status ?? null, \common\services\billing\InvoicePaymentService::CLOSED_STATUSES, true);
            $stillOwedNote = $invoiceClosed ? 'The invoice is closed and stays as it is: nothing more is owed on it. Renewals already granted are NOT reversed: disable the service if needed.' : 'The refunded amount becomes due again on the invoice.';
            $carrying = ($model->invoice->status ?? null) === Invoice::STATUS_CARRIED_FORWARD ? (new \common\services\billing\InvoicePaymentService())->openInvoiceCarrying((int) $model->invoice_id) : null;
            $stillOwedNote = $carrying !== null ? 'The balance of this invoice moved to ' . $carrying->invoice_number . ', still open: this can only be refunded once that is settled, unless Razorpay already shows the refund.' : $stillOwedNote;
            $stillOwedNote = $model->isUnapplied() ? 'This payment was not counted on the invoice, so its balance does not change.' : $stillOwedNote;
            ?>
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light fw-semibold"><i class="fas fa-undo me-1"></i> Refund</div>
                    <div class="card-body">
                        <?= Html::beginForm(['refund', 'id' => $model->id], 'post') ?>
                        <?= Html::hiddenInput('refunded_before', (string) $model->refunded_amount) ?>
                        <?php if ((int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && !(new \common\services\payment\RazorpayService())->isEnabled()): ?>
                            <div class="form-check mb-2">
                                <?= Html::checkbox('manual_return', false, ['class' => 'form-check-input', 'id' => 'manual-return', 'value' => '1']) ?>
                                <label class="form-check-label small" for="manual-return">Razorpay is off: I have returned this money myself</label>
                            </div>
                        <?php endif; ?>
                        <label class="form-label small" for="refund-amount">Amount to refund (₹)</label>
                        <input type="number" id="refund-amount" name="refund_amount" class="form-control mb-1" step="0.01" min="0.01" max="<?= $refundable ?>" value="<?= $refundable ?>" required>
                        <p class="small text-body-secondary"><?= Html::encode($refundStep) ?> A smaller amount is a partial refund, e.g. keeping a domain fee.</p>
                        <div class="d-grid gap-2">
                            <?php if ($invoiceClosed): ?>
                                <?= Html::submitButton('Refund', ['class' => 'btn btn-outline-danger', 'data-confirm' => $refundStep . "\n\n" . $stillOwedNote]) ?>
                            <?php else: ?>
                                <?= Html::submitButton('Refund (Still Owed)', [
                                'class' => 'btn btn-outline-secondary',
                                'title' => 'E.g. a wrong payment was returned: the customer still has to pay this invoice.',
                                'data-confirm' => $refundStep . "\n\n" . $stillOwedNote,
                            ]) ?>
                                <?= Html::submitButton('Refund &amp; Close Invoice', [
                                'class' => 'btn btn-outline-danger',
                                'title' => 'Nothing more is owed: the service is cancelled, or the rest is kept by agreement (e.g. a domain fee).',
                                // Not name/value: with a confirm, yii.js submits the form itself and a clicked button's value is not sent.
                                'data' => [
                                    'confirm' => $refundStep . "\n\nThe invoice is closed as Refunded: no reminders. Anything not refunded is kept. An unpaid renewal on it is billed again in full on the next run, with no credit for money kept, unless the service is disabled. Renewals already granted are NOT reversed: disable the service if needed.",
                                    'params' => ['close_invoice' => '1'],
                                ],
                            ]) ?>
                            <?php endif; ?>
                        </div>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
