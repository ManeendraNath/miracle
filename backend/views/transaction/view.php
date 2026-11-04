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
if ($model->status === Transaction::STATUS_PENDING && (int) $model->gateway_id !== Transaction::GATEWAY_RAZORPAY) {
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
$refundable = round((float) $model->amount - (float) $model->refunded_amount, 2);
// Razorpay payments are refunded through Razorpay by the form itself; others are returned by hand first.
$viaRazorpay = (int) $model->gateway_id === Transaction::GATEWAY_RAZORPAY && (string) $model->gateway_payment_id !== '' && (new RazorpayService())->isEnabled();
$refundStep = $viaRazorpay
    ? 'The amount is refunded to the customer through Razorpay now (5-7 working days to reach them).'
    : 'Only do this after the money has been returned (bank / UPI).';
?>
<div class="transaction-view container-fluid pt-3">
    <div class="mb-4">
        <?php if ($model->status === Transaction::STATUS_PENDING && (int) $model->gateway_id !== Transaction::GATEWAY_RAZORPAY && !empty($model->payment_receipt_file)): ?>
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
                        <?= Html::encode(Invoice::getStatuses()[$model->invoice->status] ?? $model->invoice->status) ?>
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
                                'label' => 'Razorpay Fee (incl. GST)',
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
                ->andWhere(['<>', 'id', (int) $model->invoice_id])->orderBy(['id' => SORT_DESC])->column();
            ?>
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4 border-warning">
                    <div class="card-header bg-warning-subtle fw-semibold"><i class="fas fa-exchange-alt me-1"></i> Unapplied payment</div>
                    <div class="card-body">
                        <p class="small">This payment arrived after invoice <?= Html::encode($model->invoice->invoice_number) ?> was already settled or closed, so it isn't counted anywhere. Refund it, or apply it to another invoice of this customer.</p>
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
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light fw-semibold"><i class="fas fa-undo me-1"></i> Refund</div>
                    <div class="card-body">
                        <?= Html::beginForm(['refund', 'id' => $model->id], 'post') ?>
                        <?= Html::hiddenInput('refunded_before', (string) $model->refunded_amount) ?>
                        <label class="form-label small" for="refund-amount">Amount to refund (₹)</label>
                        <input type="number" id="refund-amount" name="refund_amount" class="form-control mb-1" step="0.01" min="0.01" max="<?= $refundable ?>" value="<?= $refundable ?>" required>
                        <p class="small text-body-secondary"><?= Html::encode($refundStep) ?> A smaller amount is a partial refund, e.g. keeping a domain fee.</p>
                        <div class="d-grid gap-2">
                            <?= Html::submitButton('Refund (Still Owed)', [
                                'class' => 'btn btn-outline-secondary',
                                'name' => 'close_invoice',
                                'value' => '0',
                                'title' => 'E.g. a wrong payment was returned: the customer still has to pay this invoice.',
                                'data-confirm' => $refundStep . "\n\nThe refunded amount becomes due again on the invoice.",
                            ]) ?>
                            <?= Html::submitButton('Refund &amp; Close Invoice', [
                                'class' => 'btn btn-outline-danger',
                                'name' => 'close_invoice',
                                'value' => '1',
                                'title' => 'Nothing more is owed: the service is cancelled, or the rest is kept by agreement (e.g. a domain fee).',
                                'data-confirm' => $refundStep . "\n\nThe invoice is closed as Refunded: no reminders, not billed again. Anything not refunded is kept. Renewals already granted are NOT reversed: disable the service if needed.",
                            ]) ?>
                        </div>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
