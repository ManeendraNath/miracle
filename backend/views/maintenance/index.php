<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\MaintenanceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Maintenance Contracts (AMC)';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="maintenance-index card card-outline card-danger p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Track production assets under active service SLA policies, systems care hours, and support cycles</small>
        </div>
        <?= Html::a('<i class="fas fa-tools mr-1"></i> Log New AMC Contract', ['create'], ['class' => 'btn btn-danger px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn', 'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary']],
            [
                'attribute' => 'contract_title',
                'headerOptions' => ['class' => 'text-primary'],
                'format' => 'raw',
                'value' => function($model) { return '<span class="text-dark font-weight-bold">' . Html::encode($model->contract_title) . '</span>'; }
            ],
            [
                'attribute' => 'support_tier',
                'headerOptions' => ['class' => 'text-primary']
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 140px;'],
                'value' => function($model) {
                    $isActive = (int)$model->status === 1;
                    return '<span class="badge ' . ($isActive ? 'badge-success' : 'badge-secondary') . ' px-3 py-2">' . ($isActive ? 'Under Care' : 'Terminated') . '</span>';
                }
            ],
            ['class' => 'yii\grid\ActionColumn', 'headerOptions' => ['class' => 'text-center', 'style' => 'width: 100px;'], 'contentOptions' => ['class' => 'text-center']],
        ],
    ]); ?>
</div>
