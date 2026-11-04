<?php

declare(strict_types=1);

use common\models\Transaction;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Payment History';

$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
?>

<div class="mb-4">
    <h1 class="h3 fw-bold mb-1">Payment History</h1>
    <div class="text-body-secondary">Review submitted, verified and completed payments.</div>
</div>

<div class="card border-0 shadow-sm">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 px-3 py-3\">{summary}{pager}</div>",
        'summaryOptions' => ['class' => 'small text-body-secondary'],
        'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'options' => ['class' => 'mb-0'], 'listOptions' => ['class' => 'pagination pagination-sm mb-0']],
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'headerRowOptions' => ['class' => 'small text-body-secondary'],
        'emptyText' => '<div class="text-center py-5 text-body-secondary"><i class="fas fa-credit-card fa-2x mb-2 opacity-50"></i><div>No payments yet.</div></div>',
        'emptyTextOptions' => ['class' => ''],
        'columns' => [
            [
                'label' => 'Invoice',
                'format' => 'raw',
                'headerOptions' => ['class' => 'ps-3'],
                'contentOptions' => ['class' => 'ps-3 text-nowrap'],
                'value' => static fn(Transaction $model): string => Html::a(Html::encode($model->invoice->invoice_number), ['/invoice/view', 'number' => $model->invoice->invoice_number], ['class' => 'fw-semibold text-decoration-none']),
            ],
            [
                'label' => 'Reference',
                'value' => 'gateway_payment_id',
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell small text-break'],
            ],
            [
                'label' => 'Amount',
                'headerOptions' => ['class' => 'text-end'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'format' => 'raw',
                'value' => static fn(Transaction $model): string => $formatter->asCurrency($model->amount, 'INR')
                    . ((float) $model->refunded_amount > 0 ? '<div class="small text-body-secondary">' . $formatter->asCurrency($model->refunded_amount, 'INR') . ' refunded</div>' : ''),
            ],
            [
                'label' => 'Status',
                'format' => 'raw',
                'value' => static function (Transaction $model): string {
                    $class = match ($model->status) {
                        Transaction::STATUS_CAPTURED => 'bg-success-subtle text-success-emphasis',
                        Transaction::STATUS_PENDING => 'bg-warning-subtle text-warning-emphasis',
                        Transaction::STATUS_FAILED => 'bg-danger-subtle text-danger-emphasis',
                        default => 'bg-secondary-subtle text-secondary-emphasis',
                    };
                    return Html::tag('span', Html::encode($model->status), ['class' => 'badge ' . $class]);
                },
            ],
            [
                'label' => 'Submitted',
                'value' => 'created_at',
                'format' => ['datetime', 'php:d M Y, h:i A'],
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell small text-nowrap'],
            ],
            [
                'label' => 'Confirmed',
                'value' => static fn(Transaction $model): string => $model->paid_at ? $formatter->asDatetime($model->paid_at, 'php:d M Y, h:i A') : '-',
                'headerOptions' => ['class' => 'd-none d-lg-table-cell'],
                'contentOptions' => ['class' => 'd-none d-lg-table-cell small text-nowrap'],
            ],
            [
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-end text-nowrap pe-3'],
                'value' => static fn(Transaction $model): string => ($model->status === Transaction::STATUS_CAPTURED
                        ? Html::a('<i class="fas fa-download"></i>', ['receipt', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary me-1', 'target' => '_blank', 'title' => 'Receipt', 'aria-label' => 'Receipt'])
                        : '')
                    . Html::a('View', ['view', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary']),
            ],
        ],
    ]) ?>
</div>
