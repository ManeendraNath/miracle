<?php

use common\helpers\AdminIndexHelper;
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
$this->title = 'Transactions';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>
<div class="transaction-index">

    <div class="card card-outline card-primary shadow-sm">
        <?= AdminIndexHelper::pageHeader('Review customer payments, payment proofs and refunds.') ?>
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => ['class' => 'text-muted small mb-0'],
                'tableOptions' => ['class' => 'table table-striped table-hover align-middle mb-0 text-center'],
                'filterRowOptions' => ['class' => 'bg-light'],
                'columns' => [
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
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
                        'filter' => GridFilterHelper::select2($searchModel, 'gateway_id', Transaction::getGateways(), 'All Methods'),
                        'format' => 'raw',
                        'value' => static function (Transaction $model): string {
                            [$icon, $class] = match ((int) $model->gateway_id) {
                                Transaction::GATEWAY_RAZORPAY => ['fa-credit-card', 'bg-dark'],
                                Transaction::GATEWAY_UPI_BANK => ['fa-university', 'bg-info'],
                                default => ['fa-hand-holding-usd', 'bg-secondary'],
                            };
                            return Html::tag('span', '<i class="fas ' . $icon . ' me-1"></i> ' . Html::encode($model->gatewayLabel), ['class' => 'badge ' . $class . ' text-white px-2 py-1']);
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
                        'value' => static function (Transaction $model): string {
                            return Html::tag('span', Yii::$app->formatter->asCurrency($model->amount, 'INR'), ['class' => 'fw-bold text-success']);
                        },
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'headerOptions' => ['style' => 'width: 160px;', 'class' => 'text-primary'],
                        'value' => static function (Transaction $model): string {
                            $badge = match ($model->status) {
                                Transaction::STATUS_CAPTURED => 'bg-success',
                                Transaction::STATUS_PENDING => 'bg-warning text-dark',
                                Transaction::STATUS_AUTHORIZED => 'bg-info text-dark',
                                Transaction::STATUS_FAILED => 'bg-danger',
                                Transaction::STATUS_REFUNDED => 'bg-dark',
                                default => 'bg-secondary',
                            };
                            return Html::tag('span', Html::encode(Transaction::optsStatus()[$model->status] ?? $model->status), ['class' => 'badge ' . $badge . ' px-2 py-1']);
                        },
                        'filter' => GridFilterHelper::select2($searchModel, 'status', Transaction::optsStatus(), 'All Statuses'),
                    ],
                    [
                        'attribute' => 'created_at',
                        'label' => 'Date Added',
                        'format' => ['datetime', 'php:d M Y, h:i A'],
                        // Range picker: a typed date would fail validation and silently drop every filter.
                        'filter' => GridFilterHelper::dateRange($searchModel, 'created_at_range', 'Added dates'),
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
                        'filter' => GridFilterHelper::dateRange($searchModel, 'date_range', 'Paid date'),
                    ],
                    GridActionHelper::crudColumn(template: '{view} {update}'),
                ],
            ]);
            ?>
        </div>
    </div>
</div>
