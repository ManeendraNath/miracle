<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\HostingSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Hosting Subscriptions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="hosting-index card card-outline card-success p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Track web hosting cluster arrays, packages allocation, disk quotas, and server bandwidth allocations</small>
        </div>
        <?= Html::a('<i class="fas fa-server mr-1"></i> Add Hosting Package', ['create'], ['class' => 'btn btn-success px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn', 'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary']],
            [
                'attribute' => 'server_ip',
                'headerOptions' => ['class' => 'text-primary'],
                'value' => function($model) { return Html::encode($model->server_ip); }
            ],
            [
                'attribute' => 'package_name',
                'headerOptions' => ['class' => 'text-primary']
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 120px;'],
                'value' => function($model) {
                    $isActive = (int)$model->status === 1;
                    return '<span class="badge ' . ($isActive ? 'badge-success' : 'badge-warning') . ' px-2 py-1">' . ($isActive ? 'Active' : 'Suspended') . '</span>';
                }
            ],
            ['class' => 'yii\grid\ActionColumn', 'headerOptions' => ['class' => 'text-center', 'style' => 'width: 100px;'], 'contentOptions' => ['class' => 'text-center']],
        ],
    ]); ?>
</div>
