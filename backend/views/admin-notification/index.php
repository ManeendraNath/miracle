<?php

use common\helpers\GridActionHelper;
use common\helpers\AdminIndexHelper;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Admin Notifications';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="admin-notification-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Review operational alerts and administrator notifications.') ?>
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
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
                        'format' => ['datetime', 'php:d M Y h:i A'],
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