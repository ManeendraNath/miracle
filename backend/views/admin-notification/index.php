<?php

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Admin Notifications';
?>

<div class="card">

    <div class="card-header">
        <strong>
            Admin Notifications
        </strong>
    </div>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'title',
            'message:ntext',
            [
                'attribute' => 'created_at',
                'format' => [
                    'datetime',
                    'php:d M Y h:i A'
                ],
            ],
            [
                'attribute' => 'is_read',
                'value' =>
                static fn($model) =>
                $model->is_read ? 'Read' : 'Unread',
            ],
            [
                'class' =>
                yii\grid\ActionColumn::class,
                'template' =>
                '{read}',
                'buttons' => [
                    'read' =>
                    static function (
                            $url,
                            $model
                    ) {

                        return Html::a(
                                'Open',
                                [
                                    'read',
                                    'id' => $model->id,
                                ],
                                [
                                    'class' =>
                                    'btn btn-sm btn-primary',
                                ]
                        );
                    },
                ],
            ],
        ],
    ])
    ?>

</div>