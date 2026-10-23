<?php

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Transaction;
use yii\bootstrap5\Html;
use yii\grid\GridView;

/** @var yii\web\View $this */
/** @var common\models\TransactionSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Financial Transactions Ledger';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>
<div class="transaction-index container-fluid pt-3">

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => GridPaginationHelper::gridLayout(),
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
                        'value' => static function (Transaction $model): string {
                            $status = strtolower((string) $model->status);
                            $badge = match ($status) {
                                'captured',
                                'success' => 'bg-success',
                                'pending' => 'bg-warning text-dark',
                                'failed' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                            return Html::tag('span', Html::encode($model->status), ['class' => 'badge ' . $badge . ' px-2 py-1']);
                        },
                        'filter' => GridFilterHelper::select2($searchModel, 'status', [
                            'Pending' => 'Pending',
                            'Captured' => 'Captured',
                            'Failed' => 'Failed',
                                ], 'All Statuses'),
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
                        'value' => static function (Transaction $model): string {
                            if (empty($model->paid_at)) {
                                return 'Awaiting Settlement';
                            }

                            return Yii::$app->formatter->asDatetime(
                                    $model->paid_at,
                                    'php:d M Y, h:i A'
                            );
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
