<?php

use common\helpers\GridActionHelper;
use common\helpers\AdminIndexHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Admin Notifications';
$this->params['breadcrumbs'][] = $this->title;

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