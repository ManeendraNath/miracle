<?php
declare(strict_types=1);

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Maintenance;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Maintenance Contracts (AMC)';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);

$users = ArrayHelper::map(
        User::find()
                ->orderBy([
                    'username' => SORT_ASC,
                ])
                ->all(),
        'id',
        'username'
);
?>

<div class="maintenance-index">

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="card-title mb-1">
                        Maintenance Contracts (AMC)
                    </h3>

                    <div class="text-muted small">
                        Manage client maintenance services, expiry dates and renewal status.
                    </div>

                </div>

<?=
Html::a(
        '<i class="fas fa-plus mr-1"></i> Add Maintenance',
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
                            'value' => static function (
                                    Maintenance $model
                            ): string {
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
                                'style' => 'min-width: 160px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-nowrap',
                            ],
                        ],
                        [
                            'attribute' => 'website_url',
                            'label' => 'Website',
                            'format' => 'raw',
                            'value' => static function (
                                    Maintenance $model
                            ): string {
                                return Html::a(
                                        Html::encode($model->website_url),
                                        $model->website_url,
                                        [
                                            'target' => '_blank',
                                            'rel' => 'noopener noreferrer',
                                            'class' => 'fw-semibold',
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'min-width: 220px;',
                            ],
                        ],
                        [
                            'attribute' => 'start_date',
                            'label' => 'Started',
                            'format' => [
                                'date',
                                'php:d M Y',
                            ],
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
                                    Maintenance::getStatuses(),
                                    'All Statuses'
                            ),
                            'value' => static function (
                                    Maintenance $model
                            ): string {
                                $statuses = Maintenance::getStatuses();
                                $status = (int) $model->status;

                                $label = $statuses[$status] ?? 'Unknown';

                                $class = match ($status) {
                                    1 => 'bg-success',
                                    2 => 'bg-warning text-dark',
                                    0 => 'bg-secondary',
                                    default => 'bg-dark',
                                };

                                return Html::tag(
                                        'span',
                                        Html::encode($label),
                                        [
                                            'class' => 'badge ' . $class,
                                        ]
                                );
                            },
                            'headerOptions' => [
                                'style' => 'width: 150px;',
                            ],
                            'contentOptions' => [
                                'class' => 'text-center',
                            ],
                        ],
                        GridActionHelper::crudColumn(
                                deleteMessage: 'Are you sure you want to delete this maintenance service?'
                        ),
                    ],
                ])
                ?>

            </div>

        </div>

    </div>

</div>
