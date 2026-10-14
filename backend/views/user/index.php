<?php

use common\helpers\GridFilterHelper;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var common\models\UserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Client Users Directory';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index card card-outline card-primary p-4 shadow-sm">

    <!-- Cleaned Header Wrapper Section (No Duplicate h1 elements) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Review, filter, or toggle status privileges for client customer accounts profiles</small>
        </div>
        <?= Html::a('<i class="fas fa-user-plus mr-1"></i> Add New Client', ['create'], ['class' => 'btn btn-success px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel, // Enables column data filters row
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-primary font-weight-bold'],
            ],
            [
                'attribute' => 'username',
                'label' => 'Client Username',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function ($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->username) . '</span>';
                }
            ],
            [
                'attribute' => 'email',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'email',
            ],
            [
                'attribute' => 'status',
                'label' => 'Account Status',
                'filter' => GridFilterHelper::select2(
                        $searchModel,
                        'status',
                        User::getStatuses(),
                        'All Statuses'
                ),
                'format' => 'raw',
                'value' => static function (User $model): string {
                    $status = (int) $model->status;
                    $statuses = User::getStatuses();

                    switch ($status) {
                        case User::getStatusActive():
                            $class = 'bg-success';
                            break;

                        case User::getStatusSuspended():
                            $class = 'bg-warning text-dark';
                            break;

                        case User::getStatusDeleted():
                            $class = 'bg-danger';
                            break;

                        default:
                            $class = 'bg-secondary';
                            break;
                    }

                    return Html::tag(
                            'span',
                            Html::encode($statuses[$status] ?? 'Unknown'),
                            [
                                'class' => 'badge ' . $class,
                            ]
                    );
                },
            ],
            [
                'attribute' => 'created_at',
                'label' => 'Created At',
                'filter' => GridFilterHelper::dateRange(
                        $searchModel,
                        'created_at_range',
                        'Created date'
                ),
                'value' => static function (User $model): string {
                    return Yii::$app->formatter->asDatetime(
                            $model->created_at,
                            'php:d M Y, h:i A'
                    );
                },
                'headerOptions' => [
                    'style' => 'width: 220px;',
                ],
            ],
            [
                'attribute' => 'updated_at',
                'label' => 'Updated At',
                'filter' => GridFilterHelper::dateRange(
                        $searchModel,
                        'updated_at_range',
                        'Updated date'
                ),
                'value' => static function (User $model): string {
                    return Yii::$app->formatter->asDatetime(
                            $model->updated_at,
                            'php:d M Y, h:i A'
                    );
                },
                'headerOptions' => [
                    'style' => 'width: 220px;',
                ],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Actions Panel',
                'headerOptions' => ['class' => 'text-primary font-weight-bold text-center', 'style' => 'width: 100px;'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{delete}',
                'buttons' => [
                    'delete' => function ($url, $model) {
                        return Html::a('<i class="fas fa-trash-alt"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-xs btn-outline-danger px-2 py-1 rounded',
                            'title' => 'Permanently Erase Profile',
                            'data' => [
                                'confirm' => 'This action completely deletes this user from records. Proceed?',
                                'method' => 'post',
                            ],
                        ]);
                    },
                ],
            ],
        ],
    ]);
    ?>
</div>
