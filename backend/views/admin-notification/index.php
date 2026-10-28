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
                    'title',
                    'message:ntext',
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