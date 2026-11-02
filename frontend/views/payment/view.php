<?php

declare(strict_types=1);

use common\models\Transaction;
use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var common\models\Transaction $model */
$this->title = 'Payment #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Payment History', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$methods = [
    Transaction::GATEWAY_MANUAL => 'Manual',
    Transaction::GATEWAY_RAZORPAY => 'Razorpay',
    Transaction::GATEWAY_UPI_BANK => 'UPI / Bank Transfer',
];
$statusClass = match ($model->status) {
    Transaction::STATUS_CAPTURED => 'bg-success',
    Transaction::STATUS_PENDING => 'bg-warning text-dark',
    Transaction::STATUS_FAILED => 'bg-danger',
    default => 'bg-secondary',
};
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><?= Html::encode($this->title) ?></h2>
        <div>
            <?= Html::a('Back', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?php if ($model->status === Transaction::STATUS_CAPTURED): ?>
                <?= Html::a('Download Receipt', ['receipt', 'id' => $model->id], ['class' => 'btn btn-primary', 'target' => '_blank']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <?=
        DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-striped mb-0'],
            'attributes' => [
                [
                    'label' => 'Invoice',
                    'format' => 'raw',
                    'value' => $model->invoice
                        ? Html::a(Html::encode($model->invoice->invoice_number), ['/invoice/view', 'number' => $model->invoice->invoice_number])
                        : '-',
                ],
                [
                    'label' => 'Amount',
                    'value' => Yii::$app->formatter->asCurrency($model->amount, 'INR'),
                ],
                [
                    'label' => 'Method',
                    'value' => $methods[(int) $model->gateway_id] ?? '-',
                ],
                [
                    'label' => 'Reference',
                    'value' => $model->gateway_payment_id ?: '-',
                ],
                [
                    'label' => 'Status',
                    'format' => 'raw',
                    'value' => Html::tag('span', Html::encode($model->status), ['class' => 'badge ' . $statusClass]),
                ],
                [
                    'label' => 'Submitted',
                    'value' => Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A'),
                ],
                [
                    'label' => 'Confirmed',
                    'value' => $model->paid_at ? Yii::$app->formatter->asDatetime($model->paid_at, 'php:d M Y, h:i A') : '-',
                ],
                [
                    'label' => 'Your Notes',
                    'value' => $model->user_payment_notes ?: '-',
                ],
            ],
        ])
        ?>
    </div>
</div>
