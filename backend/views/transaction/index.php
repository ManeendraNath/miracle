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

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?=
            GridView::widget([
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
                        'label' => 'Invoice',
                        'format' => 'raw',
                        'value' => static function (Transaction $model): string {
                            if ($model->invoice === null) {
                                return '-';
                            }
                            return Html::a(Html::encode($model->invoice->invoice_number), ['invoice/view', 'number' => $model->invoice->invoice_number]);
                        },
                    ],
                    [
                        'attribute' => 'gateway_id',
                        'label' => 'Method',
                        'filter' => [1 => 'Razorpay', 2 => 'Manual UPI / Bank'],
                        'format' => 'raw',
                        'value' => function ($model) {
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
                        'value' => function ($model) {
                            return $model->gateway_payment_id ?: 'N/A';
                        }
                    ],
                    [
                        'attribute' => 'amount',
                        'format' => 'raw',
                        'value' => function ($model) {
                            return Html::tag('span', '₹' . number_format($model->amount, 2), ['class' => 'fw-bold text-success']);
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'headerOptions' => ['style' => 'width: 160px;', 'class' => 'text-primary'],
                        'value' => function ($model) {
                            $status = strtolower($model->status);
                            $badge = 'badge-secondary';
                            if ($status === 'captured' || $status === 'success') {
                                $badge = 'badge-success';
                            }
                            if ($status === 'pending') {
                                $badge = 'badge-warning';
                            }
                            if ($status === 'failed') {
                                $badge = 'badge-danger';
                            }
                            return '<span class="badge ' . $badge . ' px-3 py-2 text-uppercase d-block text-center">' . Html::encode($model->status) . '</span>';
                        },
                        // DROPDOWN FILTER ACTIVATION
                        'filter' => [
                            'Pending' => 'Pending',
                            'Captured' => 'Captured',
                            'Failed' => 'Failed'
                        ],
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Date Added',
                        'format' => ['date', 'php:Y-m-d H:i'],
                    ],
                    [
                        'attribute' => 'date_range',
                        'label' => 'Payment Date',
                        'headerOptions' => ['class' => 'text-primary font-weight-bold', 'style' => 'width: 260px;'],
                        'value' => function ($model) {
                            return !empty($model->paid_at) ? date('d-M-Y h:i A', $model->paid_at) : 'Awaiting Settlement';
                        },
                        'filter' => \kartik\daterange\DateRangePicker::widget([
                            'model' => $searchModel,
                            'attribute' => 'date_range',
                            'convertFormat' => true,
                            'pluginOptions' => [
                                'locale' => ['format' => 'Y-m-d', 'separator' => ' - '],
                                'opens' => 'left'
                            ],
                            'options' => ['class' => 'form-control form-control-sm', 'placeholder' => 'Filter Sales Spread...']
                        ]),
                    ],
                    GridActionHelper::crudColumn(template: '{view} {update}'),
                ],
            ]);
            ?>
        </div>
    </div>
</div>
