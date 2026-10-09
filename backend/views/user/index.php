<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

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

    <?= GridView::widget([
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
                'value' => function($model) {
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
                'label' => 'Account Status (Click to Toggle)',
                'headerOptions' => ['class' => 'text-primary font-weight-bold', 'style' => 'width: 220px;'],
                'format' => 'raw',
                'value' => function($model) {
                    $isActive = (int)$model->status === 10;
                    $badge = $isActive ? 'badge-success' : 'badge-secondary';
                    $icon = $isActive ? 'fa-user-check' : 'fa-user-slash';
                    $text = $isActive ? 'Active / Unlocked' : 'Inactive / Suspended';

                    // ⚡ Clickable Status Switch Link targeting the Toggle action endpoint
                    return Html::a(
                        "<i class='fas {$icon} mr-1'></i> {$text}",
                        ['toggle-status', 'id' => $model->id],
                        [
                            'class' => "badge {$badge} p-2 text-decoration-none shadow-sm d-inline-block w-100 text-center",
                            'title' => 'Click to alter status configuration privilege',
                            'data' => [
                                'confirm' => "Are you sure you want to change the active status for client account '{$model->username}'?",
                                'method' => 'post',
                            ],
                        ]
                    );
                }
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
    ]); ?>
</div>
