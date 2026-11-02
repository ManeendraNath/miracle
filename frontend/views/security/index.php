<?php

declare(strict_types=1);

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Account Security';

$this->params['breadcrumbs'][] = $this->title;

$formatter = Yii::$app->formatter;
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-end gap-3 mb-4">
    <div>
        <h1 class="h3 fw-bold mb-1">Account Security</h1>
        <div class="text-body-secondary">Review successful sign-ins recorded for your account.</div>
    </div>
    <?= Html::a('<i class="fas fa-key me-1"></i> Change Password', ['/account/change-password'], ['class' => 'btn btn-primary']) ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent py-3">
        <h2 class="h6 fw-bold mb-0">Sign-in History</h2>
    </div>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'layout' => "<div class=\"table-responsive\">{items}</div>\n<div class=\"d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 px-3 py-3\">{summary}{pager}</div>",
        'summaryOptions' => ['class' => 'small text-body-secondary'],
        'pager' => ['class' => \yii\bootstrap5\LinkPager::class, 'options' => ['class' => 'mb-0'], 'listOptions' => ['class' => 'pagination pagination-sm mb-0']],
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'headerRowOptions' => ['class' => 'small text-body-secondary'],
        'emptyText' => '<div class="text-center py-5 text-body-secondary"><i class="fas fa-shield-alt fa-2x mb-2 opacity-50"></i><div>No sign-ins recorded yet.</div></div>',
        'emptyTextOptions' => ['class' => ''],
        'columns' => [
            [
                'label' => 'Signed In',
                'format' => 'raw',
                'headerOptions' => ['class' => 'ps-3'],
                'contentOptions' => ['class' => 'ps-3'],
                // On phones the browser column is hidden, so the user agent is shown under the date instead.
                'value' => static fn($model): string => '<div class="text-nowrap">' . $formatter->asDatetime($model->logged_in_at, 'php:d M Y, h:i A') . '</div>'
                    . '<div class="small text-body-secondary">' . $formatter->asRelativeTime($model->logged_in_at) . '</div>'
                    . '<div class="small text-body-secondary text-break d-md-none mt-1">' . $formatter->asText($model->user_agent) . '</div>',
            ],
            [
                'label' => 'IP Address',
                'headerOptions' => ['class' => 'pe-3 pe-md-2'],
                'contentOptions' => ['class' => 'pe-3 pe-md-2 font-monospace small text-break'],
                'value' => static fn($model) => $model->ip_address ?: '-',
            ],
            [
                'label' => 'Browser / Device',
                'format' => 'text',
                'value' => 'user_agent',
                'headerOptions' => ['class' => 'pe-3 d-none d-md-table-cell'],
                'contentOptions' => ['class' => 'pe-3 small text-body-secondary text-break d-none d-md-table-cell', 'style' => 'min-width: 14rem; max-width: 450px;'],
            ],
        ],
    ]) ?>
</div>
