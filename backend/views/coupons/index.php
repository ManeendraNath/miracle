<?php
use yii\helpers\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\CouponsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Promotional Coupons';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="coupons-index card card-outline card-warning p-4 shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <small class="text-muted">Manage promotional codes, discounts, campaign rates, and validation limits</small>
        </div>
        <?= Html::a('<i class="fas fa-tag mr-1"></i> Create Coupon', ['create'], ['class' => 'btn btn-warning text-white px-4 rounded-pill shadow-sm']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle border'],
        'summaryOptions' => ['class' => 'text-muted small mb-3'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn', 'headerOptions' => ['style' => 'width: 50px;', 'class' => 'text-primary']],
            [
                'attribute' => 'code',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary'],
                'value' => function($model) {
                    return '<strong class="text-monospace text-primary">' . Html::encode($model->code) . '</strong>';
                }
            ],
            [
                'attribute' => 'discount_value',
                'headerOptions' => ['class' => 'text-primary'],
                'value' => function($model) {
                    return (int)$model->discount_type === 1 ? '₹' . number_format($model->discount_value, 2) : $model->discount_value . '%';
                }
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-primary', 'style' => 'width: 120px;'],
                'value' => function($model) {
                    $isActive = (int)$model->status === 1;
                    return '<span class="badge ' . ($isActive ? 'badge-success' : 'badge-secondary') . ' px-3 py-2">' . ($isActive ? 'Active' : 'Paused') . '</span>';
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
