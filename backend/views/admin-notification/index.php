<?php

use common\helpers\GridActionHelper;
use common\helpers\AdminIndexHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Admin Notifications';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'What this is' => 'Alerts the system raises for staff: new tickets and leads, payments to review, invoices waiting for review, billing problems, cron job and two-factor changes. Each one is also emailed to the admin address.',
    'Bold title' => 'Not opened yet. Read and Unread are shared: once any staff member opens one, it shows as Read for everyone.',
    'Eye icon' => 'Marks it Read and takes you to the page it is about, such as the ticket or the invoice list, so you can act on it (or back here when there is none).',
    'Who sees what' => 'Managers do not see billing, payment, testimonial or security alerts; Admins and Superadmins see everything.',
];

GridViewHelper::register($this);
// No search model here: the page-size dropdown sets the size the controller's provider uses.
$dataProvider->pagination->pageSize = GridPaginationHelper::getPageSize();
?>

<div class="admin-notification-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Review operational alerts and administrator notifications.') ?>
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => ['class' => 'text-muted small mb-0'],
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
                    [
                        'attribute' => 'title',
                        'format' => 'raw',
                        'value' => static function ($model) {
                            return Html::tag(
                                'span',
                                Html::encode($model->title),
                                ['class' => $model->is_read ? '' : 'fw-bold text-primary']
                            );
                        },
                    ],
                    [
                        'attribute' => 'message',
                        'format' => 'ntext',
                        'contentOptions' => [
                            'style' => 'max-width:450px;',
                        ],
                    ],
                    [
                        'attribute' => 'created_at',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                    ],
                    [
                        'attribute' => 'is_read',
                        'value' => static fn($model) => $model->is_read ? 'Read' : 'Unread',
                    ],
                    GridActionHelper::crudColumn(template: '{view}', urlCreator: static fn (string $action, $model): array => ['read', 'id' => $model->id]),
                ],
            ])
            ?>
        </div>
    </div>
</div>