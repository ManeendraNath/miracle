<?php

declare(strict_types=1);

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Coupon;
use yii\grid\GridView;
use common\helpers\AdminIndexHelper;
use yii\helpers\Html;

$this->title = 'Promotional Coupon';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>

<div class="coupon-index">

    <div class="card card-outline card-primary shadow-sm">

        <?= AdminIndexHelper::pageHeader(
            'Manage discount codes, validity periods and usage limits.',
            'create',
            'Add Coupon',
        ) ?>

        <div class="card-body p-0">

            <div class="table-responsive">

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'layout' => GridPaginationHelper::gridLayout(),
                    'summaryOptions' => [
                        'class' => 'text-muted small mb-3',
                    ],
                    'tableOptions' => [
                        'class' => 'table table-hover table-striped align-middle mb-0',
                    ],
                    'filterRowOptions' => [
                        'class' => 'bg-dark',
                    ],
                    'columns' => [
                        [
                            'class' => 'yii\grid\SerialColumn',
                            'header' => '#',
                            'headerOptions' => [
                                'style' => 'width: 60px;',
                                'class' => 'text-center',
                            ],
                            'contentOptions' => [
                                'class' => 'text-center text-muted',
                            ],
                        ],
                        [
                            'attribute' => 'code',
                            'label' => 'Code',
                            'format' => 'raw',
                            'value' => static function (Coupon $model): string {
                                return Html::tag('code', Html::encode($model->code), [
                                    'class' => 'fw-semibold text-primary',
                                ]);
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 150px;',
                            ],
                        ],
                        [
                            'attribute' => 'name',
                            'label' => 'Campaign',
                            'headerOptions' => [
                                'style' => 'min-width: 180px;',
                            ],
                        ],
                        [
                            'attribute' => 'type',
                            'label' => 'Type',
                            'filter' => GridFilterHelper::select2(
                                $searchModel,
                                'type',
                                Coupon::getTypes(),
                                'All Types',
                            ),
                            'value' => static fn(Coupon $model): string => $model->getTypeLabel(),
                            'headerOptions' => [
                                'style' => 'min-width: 150px;',
                            ],
                        ],
                        [
                            'attribute' => 'amount',
                            'label' => 'Discount',
                            'value' => static fn(Coupon $model): string => $model->getDiscountLabel(),
                            'headerOptions' => [
                                'style' => 'min-width: 130px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap fw-semibold',
                            ],
                        ],
                        [
                            'attribute' => 'minimum_order',
                            'label' => 'Min. Order',
                            'value' => static fn(Coupon $model): string => Yii::$app->formatter->asCurrency(
                                $model->minimum_order,
                                'INR',
                            ),
                            'headerOptions' => [
                                'style' => 'min-width: 140px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'end_date',
                            'label' => 'Expires',
                            'value' => static function (Coupon $model): string {
                                if (empty($model->end_date)) {
                                    return 'No expiry';
                                }

                                return Yii::$app->formatter->asDatetime($model->end_date, 'php:d M Y, h:i A');
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 170px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'is_active',
                            'label' => 'Status',
                            'format' => 'raw',
                            'filter' => GridFilterHelper::select2(
                                $searchModel,
                                'is_active',
                                Coupon::getStatuses(),
                                'All Statuses',
                            ),
                            'value' => static function (Coupon $model): string {
                                $active = $model->is_active === Coupon::IS_ACTIVE_Y;
                                return Html::tag('span', Html::encode($model->getStatusLabel()), [
                                    'class' => $active ? 'badge bg-success' : 'badge bg-secondary',
                                ]);
                            },
                            'headerOptions' => ['style' => 'width: 140px;'],
                            'contentOptions' => ['class' => 'text-center'],
                        ],
                        GridActionHelper::crudColumn(
                            template: '{view} {update} {toggle-status}',
                            isEnabled: static fn(Coupon $model): bool => $model->is_active === Coupon::IS_ACTIVE_Y,
                        ),
                    ],
                ]) ?>

            </div>

        </div>

    </div>

</div>