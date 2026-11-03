<?php

declare(strict_types=1);

use common\models\Invoice;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var \common\services\billing\InvoicePaymentService $paymentService */

$this->title = 'My Invoices';
$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
$today = strtotime('today');
$payable = [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID];
$tabs = ['all' => 'All', 'due' => 'Due', 'paid' => 'Paid'];
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">My Invoices</h1>
        <div class="text-body-secondary">Review your invoices, balances and payment status.</div>
    </div>
    <div class="dash-stat card border-0 shadow-sm">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-3">
            <span class="dash-stat-icon <?= $outstandingTotal > 0 ? 'bg-danger-subtle text-danger-emphasis' : 'bg-success-subtle text-success-emphasis' ?>">
                <i class="fas <?= $outstandingTotal > 0 ? 'fa-file-invoice-dollar' : 'fa-check' ?>"></i>
            </span>
            <div>
                <div class="small text-body-secondary">Total outstanding</div>
                <div class="fs-5 fw-bold"><?= $formatter->asCurrency($outstandingTotal, 'INR') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent pt-3 pb-0 border-bottom">
        <ul class="nav nav-tabs card-header-tabs">
            <?php foreach ($tabs as $key => $label): ?>
                <li class="nav-item">
                    <?= Html::a(
                        $label . ' <span class="badge rounded-pill ' . ($key === 'due' && $counts['due'] ? 'bg-danger' : 'bg-secondary-subtle text-secondary-emphasis') . ' ms-1">' . $counts[$key] . '</span>',
                        ['index', 'filter' => $key],
                        ['class' => 'nav-link' . ($filter === $key ? ' active fw-semibold' : '')]
                    ) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 px-3 py-3\">{summary}{pager}</div>",
        'summaryOptions' => ['class' => 'small text-body-secondary'],
        'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'options' => ['class' => 'mb-0'], 'listOptions' => ['class' => 'pagination pagination-sm mb-0']],
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'headerRowOptions' => ['class' => 'small text-body-secondary'],
        'emptyText' => '<div class="text-center py-5 text-body-secondary"><i class="fas fa-file-invoice fa-2x mb-2 opacity-50"></i><div>'
            . ($filter === 'due' ? 'Nothing due. You are all paid up.' : 'No invoices here yet.') . '</div></div>',
        'emptyTextOptions' => ['class' => ''],
        'columns' => [
            [
                'label' => 'Invoice',
                'format' => 'raw',
                'headerOptions' => ['class' => 'ps-3'],
                'contentOptions' => ['class' => 'ps-3 text-nowrap'],
                'value' => static fn(Invoice $model): string => Html::a(Html::encode($model->invoice_number), ['view', 'number' => $model->invoice_number], ['class' => 'fw-semibold text-decoration-none']),
            ],
            [
                'label' => 'Issued',
                'format' => ['date', 'php:d M Y'],
                'value' => 'created_at',
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            ],
            [
                'label' => 'Due',
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-nowrap'],
                'value' => static function (Invoice $model) use ($formatter, $today, $payable): string {
                    if (!$model->due_date) {
                        return '-';
                    }
                    $date = $formatter->asDate($model->due_date, 'php:d M Y');
                    $overdue = in_array($model->status, $payable, true) && strtotime((string) $model->due_date) < $today;
                    return $overdue ? Html::tag('span', $date, ['class' => 'text-danger fw-semibold']) . '<div class="small text-danger">Overdue</div>' : $date;
                },
            ],
            [
                'label' => 'Amount',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-end'],
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'value' => static function (Invoice $model) use ($formatter, $paymentService): string {
                    $html = $formatter->asCurrency($model->total_payable, 'INR');
                    if ($model->status === Invoice::STATUS_PARTIALLY_PAID) {
                        $html .= '<div class="small text-body-secondary">' . $formatter->asCurrency($paymentService->getOutstanding($model), 'INR') . ' left</div>';
                    }
                    return $html;
                },
            ],
            [
                'label' => 'Status',
                'format' => 'raw',
                'value' => static function (Invoice $model): string {
                    $class = match ($model->status) {
                        Invoice::STATUS_PAID => 'bg-success-subtle text-success-emphasis',
                        Invoice::STATUS_PARTIALLY_PAID => 'bg-warning-subtle text-warning-emphasis',
                        Invoice::STATUS_UNPAID => 'bg-danger-subtle text-danger-emphasis',
                        default => 'bg-secondary-subtle text-secondary-emphasis',
                    };
                    return Html::tag('span', Html::encode(Invoice::getStatuses()[$model->status] ?? $model->status), ['class' => 'badge ' . $class]);
                },
            ],
            [
                'format' => 'raw',
                'contentOptions' => ['class' => 'text-end text-nowrap pe-3'],
                'value' => static function (Invoice $model) use ($payable): string {
                    $pdf = Html::a('<i class="fas fa-download"></i>', ['download', 'number' => $model->invoice_number], ['class' => 'btn btn-sm btn-outline-secondary me-1', 'title' => 'Download PDF', 'aria-label' => 'Download PDF']);
                    return $pdf . (in_array($model->status, $payable, true)
                        ? Html::a('Pay', ['view', 'number' => $model->invoice_number], ['class' => 'btn btn-sm btn-success'])
                        : Html::a('View', ['view', 'number' => $model->invoice_number], ['class' => 'btn btn-sm btn-outline-primary']));
                },
            ],
        ],
    ]) ?>
</div>
