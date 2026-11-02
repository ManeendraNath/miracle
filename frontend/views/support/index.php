<?php

declare(strict_types=1);

use common\models\SupportTicket;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Support Tickets';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Support Tickets</h1>
        <div class="text-body-secondary">Ask for help and follow up on your requests.</div>
    </div>
    <div>
        <?= Html::a('<i class="fas fa-plus me-1"></i> Create Ticket', ['create'], ['class' => 'btn btn-primary']) ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 px-3 py-3\">{summary}{pager}</div>",
        'summaryOptions' => ['class' => 'small text-body-secondary'],
        'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'options' => ['class' => 'mb-0'], 'listOptions' => ['class' => 'pagination pagination-sm mb-0']],
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'headerRowOptions' => ['class' => 'small text-body-secondary'],
        'emptyText' => '<div class="text-center py-5 text-body-secondary"><i class="fas fa-headset fa-2x mb-2 opacity-50"></i><div>No support tickets found.</div></div>',
        'emptyTextOptions' => ['class' => ''],
        'columns' => [
            [
                'label' => 'Ticket',
                'format' => 'raw',
                'headerOptions' => ['class' => 'ps-3'],
                'contentOptions' => ['class' => 'ps-3'],
                'value' => static fn(SupportTicket $model): string => Html::a(Html::encode($model->subject), ['/support/view', 'number' => $model->ticket_number], ['class' => 'fw-semibold text-decoration-none text-break'])
                    . '<div class="small text-body-secondary text-nowrap">' . Html::encode($model->ticket_number) . '</div>',
            ],
            [
                'label' => 'Updated',
                'format' => ['date', 'php:d M Y'],
                'value' => 'updated_at',
                'headerOptions' => ['class' => 'd-none d-md-table-cell'],
                'contentOptions' => ['class' => 'd-none d-md-table-cell text-nowrap'],
            ],
            [
                'label' => 'Status',
                'format' => 'raw',
                'contentOptions' => ['class' => 'pe-3 text-nowrap'],
                'value' => static function (SupportTicket $model): string {
                    $class = match ($model->status) {
                        SupportTicket::STATUS_OPEN => 'bg-danger-subtle text-danger-emphasis',
                        SupportTicket::STATUS_IN_PROGRESS => 'bg-warning-subtle text-warning-emphasis',
                        SupportTicket::STATUS_RESOLVED => 'bg-success-subtle text-success-emphasis',
                        default => 'bg-secondary-subtle text-secondary-emphasis',
                    };
                    return Html::tag('span', Html::encode($model->status), ['class' => 'badge ' . $class]);
                },
            ],
        ],
    ]) ?>
</div>
