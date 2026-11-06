<?php

declare(strict_types=1);

use common\models\Transaction;
use yii\helpers\Html;

/** @var common\models\Transaction $model */
$this->title = 'Payment #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Payment History', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
$methods = [
    Transaction::GATEWAY_MANUAL => 'Manual',
    Transaction::GATEWAY_RAZORPAY => 'Razorpay',
    Transaction::GATEWAY_UPI_BANK => 'UPI / Bank Transfer',
];
$statusClass = match ($model->status) {
    Transaction::STATUS_CAPTURED => $model->isUnapplied() ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis',
    Transaction::STATUS_PENDING => 'bg-warning-subtle text-warning-emphasis',
    Transaction::STATUS_FAILED => 'bg-danger-subtle text-danger-emphasis',
    default => 'bg-secondary-subtle text-secondary-emphasis',
};
$details = [
    'Invoice' => Html::a(Html::encode($model->invoice->invoice_number), ['/invoice/view', 'number' => $model->invoice->invoice_number], ['class' => 'fw-semibold text-decoration-none']),
    'Method' => Html::encode($methods[(int) $model->gateway_id] ?? '-'),
    'Reference' => '<span class="text-break">' . Html::encode($model->customerReference() ?: '-') . '</span>',
    'Submitted' => $formatter->asDatetime($model->created_at, 'php:d M Y, h:i A'),
    'Confirmed' => $model->paid_at ? $formatter->asDatetime($model->paid_at, 'php:d M Y, h:i A') : '-',
];
if ((float) $model->refunded_amount > 0) {
    $details['Refunded'] = $formatter->asCurrency($model->refunded_amount, 'INR');
}
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1 d-flex align-items-center gap-2 flex-wrap">
            <?= Html::encode($this->title) ?>
            <span class="badge fs-6 fw-semibold <?= $statusClass ?>"><?= Html::encode($model->customerStatus()) ?></span>
        </h1>
        <div class="text-body-secondary">Details of your payment submission.</div>
    </div>
    <div class="d-flex gap-2">
        <?= Html::a('<i class="fas fa-arrow-left me-1"></i> Back', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        <?php if ($model->status === Transaction::STATUS_CAPTURED && !$model->isUnapplied()): ?>
            <?= Html::a('<i class="fas fa-download me-1"></i> Download Receipt', ['receipt', 'id' => $model->id], ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="small text-body-secondary">Amount</div>
        <div class="display-6 fw-bold mb-4"><?= $formatter->asCurrency($model->amount, 'INR') ?></div>
        <?php if ($model->status === Transaction::STATUS_CAPTURED && $model->isUnapplied()): ?>
            <div class="alert alert-warning small">
                <i class="fas fa-info-circle me-1"></i>
                We received this payment, but its invoice was already settled or closed, so it has not been counted on the invoice. We will refund it or apply it to another invoice.
            </div>
        <?php endif; ?>

        <dl class="row mb-0">
            <?php foreach ($details as $label => $value): ?>
                <dt class="col-sm-4 col-lg-3 fw-normal text-body-secondary"><?= $label ?></dt>
                <dd class="col-sm-8 col-lg-9"><?= $value ?></dd>
            <?php endforeach; ?>
            <?php if ((int) $model->gateway_id === Transaction::GATEWAY_UPI_BANK && (string) $model->payment_receipt_file !== ''): // Only proofs carry the customer's own notes; other rows hold staff notes. ?>
            <dt class="col-sm-4 col-lg-3 fw-normal text-body-secondary">Your Notes</dt>
            <dd class="col-sm-8 col-lg-9 mb-0 text-break"><?= $model->customerNotes() !== '' ? nl2br(Html::encode($model->customerNotes())) : '-' ?></dd>
            <?php endif; ?>
        </dl>
    </div>
</div>
