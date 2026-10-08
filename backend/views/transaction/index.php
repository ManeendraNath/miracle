<?php

use yii\bootstrap5\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var common\models\TransactionSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

$this->title = 'Financial Transactions Ledger';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="transaction-index container-fluid pt-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-striped table-hover align-middle mb-0 text-center'],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['style' => 'width: 80px;'],
                    ],
                    [
                        'attribute' => 'invoice_id',
                        'label' => 'Invoice Ref',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::a('#' . $model->invoice_id, ['invoice/view', 'id' => $model->invoice_id], ['class' => 'fw-bold']);
                        },
                    ],
                    [
                        'attribute' => 'gateway_id',
                        'label' => 'Method',
                        'filter' => [1 => 'Razorpay', 2 => 'Manual UPI / Bank'],
                        'format' => 'raw',
                        'value' => function($model) {
                            if ($model->gateway_id == 2) {
                                return Html::tag('span', '<i class="fas fa-university me-1"></i> Manual UPI', ['class' => 'badge bg-info text-white px-2 py-1']);
                            }
                            return Html::tag('span', '<i class="fas fa-credit-card me-1"></i> Razorpay', ['class' => 'badge bg-dark text-white px-2 py-1']);
                        },
                    ],
                    [
                        'attribute' => 'gateway_payment_id',
                        'label' => 'UTR / Ref No',
                        'headerOptions' => ['class' => 'text-start'],
                        'contentOptions' => ['class' => 'text-start text-monospace font-weight-bold small'],
                        'value' => function($model) {
                            return $model->gateway_payment_id ?: 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'amount',
                        'format' => 'raw',
                        'value' => function($model) {
                            return Html::tag('span', '₹' . number_format($model->amount, 2), ['class' => 'fw-bold text-success']);
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'filter' => ['Pending' => 'Pending', 'Captured' => 'Captured', 'Failed' => 'Failed'],
                        'format' => 'raw',
                        'value' => function($model) {
                            $colors = ['Pending' => 'bg-warning', 'Captured' => 'bg-success', 'Failed' => 'bg-danger'];
                            $class = $colors[$model->status] ?? 'bg-secondary';
                            return Html::tag('span', $model->status, ['class' => "badge $class px-3 py-1.5 rounded-pill"]);
                        },
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Date Added',
                        'format' => ['date', 'php:Y-m-d H:i'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'header' => 'Actions Dashboard',
                        'template' => '{view} {approve}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye text-primary"></i>', $url, [
                                    'title' => 'Open Audit Profile',
                                    'class' => 'btn btn-sm btn-light border me-1'
                                ]);
                            },
                            'approve' => function ($url, $model) {
                                if ($model->status === 'Pending' && $model->gateway_id == 2) {
                                    return Html::a('<i class="fas fa-check text-success"></i>', ['approve', 'id' => $model->id], [
                                        'title' => 'Quick Approve Reference',
                                        'class' => 'btn btn-sm btn-light border',
                                        'data' => [
                                            'confirm' => 'Verify this UPI reference and match account credits immediately?',
                                            'method' => 'post',
                                        ],
                                    ]);
                                }
                                return '';
                            },
                        ],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
