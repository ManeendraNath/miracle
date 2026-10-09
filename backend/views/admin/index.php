<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var backend\models\AdminSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Admins';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-index card card-outline card-primary p-4 shadow-sm">
    
    <!-- Cleaned Header Wrapper Section (No Duplicate h1 blocks) -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Review, filter, or update global administrative control accounts profiles</small>
        </div>
        <?= Html::a('<i class="fas fa-user-plus mr-1"></i> Create Admin', ['create'], ['class' => 'btn btn-success px-4 rounded-pill shadow-sm']) ?>
    </div>

    <!-- Polished GridView containing Column-Level Input Filters -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel, // 🚀 THE CURE: This enables the empty filtering row input boxes shown in your image!
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            [
                'class' => 'yii\grid\SerialColumn',
                'header' => '#',
                'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary font-weight-bold'],
            ],
            [
                'attribute' => 'id',
                'label' => 'System ID Reference',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function($model) {
                    return '<span class="text-secondary font-weight-bold">' . $model->id . '</span>';
                }
            ],
            [
                'attribute' => 'username',
                'label' => 'Unique Profile Handle',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function($model) {
                    return '<span class="font-weight-bold text-dark">' . Html::encode($model->username) . '</span>';
                }
            ],
            [
                'attribute' => 'email',
                'label' => 'Email',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'email',
            ],
            [
                'attribute' => 'auth_key',
                'label' => 'Persistent Cookie Validation Token',
                'headerOptions' => ['class' => 'text-primary font-weight-bold'],
                'format' => 'raw',
                'value' => function($model) {
                    return '<code class="text-xs text-muted">' . Html::encode(substr($model->auth_key, 0, 15)) . '...</code>';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Actions Panel',
                'headerOptions' => ['class' => 'text-primary font-weight-bold text-center', 'style' => 'width: 150px;'],
                'contentOptions' => ['class' => 'text-center'],
                'template' => '{update} {delete}',
                'buttons' => [
                    'update' => function ($url, $model) {
                        return Html::a('<i class="fas fa-edit"></i>', ['update', 'id' => $model->id], [
                            'class' => 'btn btn-xs btn-outline-primary mx-1',
                            'title' => 'Modify Settings'
                        ]);
                    },
                    'delete' => function ($url, $model) {
                        return Html::a('<i class="fas fa-trash-alt"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-xs btn-outline-danger mx-1',
                            'data' => [
                                'confirm' => 'Are you certain you want to revoke this administrative account status profile?',
                                'method' => 'post',
                            ],
                            'title' => 'Delete Account'
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
</div>
