<?php
declare(strict_types=1);

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Hosting;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Hosting Subscriptions';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);

$users = ArrayHelper::map(User::find()->orderBy(['username' => SORT_ASC])->all(), 'id', 'username');
?>

<div class="hosting-index">

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>
                    <h3 class="card-title mb-1">
                        Hosting Subscriptions
                    </h3>

                    <div class="text-muted small">
                        Manage client hosting accounts, server access, expiry dates and renewal settings.
                    </div>
                </div>

<?=
Html::a(
        '<i class="fas fa-plus mr-1"></i> Add Hosting',
        ['create'],
        [
            'class' => 'btn btn-sm btn-primary',
        ]
)
?>

            </div>

        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <?=
                GridView::widget([
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
                            'attribute' => 'user_id',
                            'label' => 'Client',
                            'format' => 'raw',
                            'value' => static function (Hosting $model): string {
                                if ($model->user === null) {
                                    return '-';
                                }

                                $username = $model->user->username ?: 'Unknown';
                                $email = $model->user->email ?: 'Email not available';

                                return Html::tag(
                                        'span',
                                        Html::encode($username),
                                        [
                                            'class' => 'fw-semibold text-primary',
                                            'title' => $email,
                                            'data-bs-toggle' => 'tooltip',
                                            'data-bs-placement' => 'top',
                                            'style' => 'cursor: help;',
                                        ]
                                );
                            },
                            'filter' => GridFilterHelper::select2(
                                    $searchModel,
                                    'user_id',
                                    $users,
                                    'All Clients'
                            ),
                            'headerOptions' => [
                                'style' => 'min-width: 150px; width: 170px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'primary_domain',
                            'label' => 'Primary Domain',
                            'format' => 'raw',
                            'value' => static function (Hosting $model): string {
                                return Html::tag(
                                        'span',
                                        Html::encode($model->primary_domain),
                                        [
                                            'class' => 'fw-semibold text-dark',
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 190px;',
                            ],
                        ],
                        [
                            'attribute' => 'cpanel_ip',
                            'label' => 'cPanel IP',
                            'headerOptions' => [
                                'style' => 'min-width: 140px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'current_expiry_date',
                            'label' => 'Expiry',
                            'filter' => GridFilterHelper::dateRange(
                                    $searchModel,
                                    'expiry_date_range',
                                    'Expiry dates'
                            ),
                            'format' => [
                                'date',
                                'php:d M Y',
                            ],
                            'headerOptions' => [
                                'style' => 'min-width: 200px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'status',
                            'format' => 'raw',
                            'filter' => GridFilterHelper::select2(
                                    $searchModel,
                                    'status',
                                    Hosting::getStatuses(),
                                    'All Statuses'
                            ),
                            'value' => static function (Hosting $model): string {
                                $status = (int) $model->status;
                                $statuses = Hosting::getStatuses();

                                $class = match ($status) {
                                    Hosting::STATUS_ACTIVE => 'bg-success',
                                    Hosting::STATUS_INACTIVE => 'bg-secondary',
                                    Hosting::STATUS_SUSPENDED => 'bg-warning text-dark',
                                    default => 'bg-dark',
                                };

                                return Html::tag(
                                        'span',
                                        Html::encode(
                                                $statuses[$status] ?? 'Unknown'
                                        ),
                                        [
                                            'class' => 'badge ' . $class,
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'width: 170px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-center',
                            ],
                        ],
                        GridActionHelper::crudColumn(
                                deleteMessage: 'Are you sure you want to delete this hosting service?'
                        ),
                    ],
                ])
                ?>

            </div>

        </div>

    </div>

</div>
`