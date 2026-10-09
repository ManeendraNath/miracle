<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\AuditRequestSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Audit Requests';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="audit-request-index card card-outline card-primary p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Track, process, and filter website technical audit and review optimization requests</small>
        </div>
        <?= Html::a('<i class="fas fa-plus-circle mr-1"></i> Add Audit Request', ['create'], ['class' => 'btn btn-success px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn', 'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary']],
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width: 80px;', 'class' => 'text-primary']
            ],
            [
                'attribute' => 'website_url',
                'format' => 'url',
                'headerOptions' => ['class' => 'text-primary']
            ],
            [
                'attribute' => 'client_name',
                'headerOptions' => ['class' => 'text-primary']
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['style' => 'width: 140px;', 'class' => 'text-primary'],
                'value' => function($model) {
                    $badge = strtolower($model->status) === 'pending' ? 'badge-warning' : 'badge-success';
                    return '<span class="badge ' . $badge . ' px-3 py-2 text-uppercase">' . Html::encode($model->status) . '</span>';
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'header' => 'Actions',
                'headerOptions' => ['class' => 'text-primary text-center', 'style' => 'width: 120px;'],
                'contentOptions' => ['class' => 'text-center'],
            ],
        ],
    ]); ?>
</div>
