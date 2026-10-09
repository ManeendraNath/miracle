<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\DomainsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Domains Registry';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="domains-index card card-outline card-info p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Monitor active client domain records, nameservers, registration dates, and upcoming expiries</small>
        </div>
        <?= Html::a('<i class="fas fa-globe mr-1"></i> Register Domain', ['create'], ['class' => 'btn btn-info px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn', 'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary']],
            [
                'attribute' => 'domain_name',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary'],
                'value' => function($model) {
                    return '<span class="text-dark font-weight-bold">' . Html::encode($model->domain_name) . '</span>';
                }
            ],
            [
                'attribute' => 'expiry_date',
                'format' => ['date', 'php:d M Y'],
                'headerOptions' => ['class' => 'text-primary']
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 120px;'],
                'value' => function($model) {
                    $isActive = (int)$model->status === 1;
                    return '<span class="badge ' . ($isActive ? 'badge-success' : 'badge-danger') . ' px-2 py-1">' . ($isActive ? 'Live' : 'Expired') . '</span>';
                }
            ],
            ['class' => 'yii\grid\ActionColumn', 'headerOptions' => ['class' => 'text-center', 'style' => 'width: 100px;'], 'contentOptions' => ['class' => 'text-center']],
        ],
    ]); ?>
</div>
