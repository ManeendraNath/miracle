<?php

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
                    [
                        'class' => yii\grid\ActionColumn::class,
                        'template' => '{read}',
                        'buttons' => [
                            'read' => static function ($url, $model) {
                                return Html::a('Open', ['read', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']);
                            },
                        ],
                    ],
                ],
            ])
            ?>
        </div>
    </div>
</div>