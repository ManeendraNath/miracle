<?php

declare(strict_types=1);

use common\helpers\AdminIndexHelper;
use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Maintenance;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Maintenance Contracts';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Add Maintenance' => 'Record a client\'s maintenance (AMC) contract with its expiry date and renewal price. Active contracts with a price are invoiced automatically a set number of days before they expire.',
    'Eye icon' => 'Open the contract\'s details.',
    'Pencil icon' => 'Edit the contract, including its expiry date, status and renewal prices.',
    'Ban icon (Disable)' => 'Shown on Active contracts. Makes the contract Cancelled so it is not renewed: its renewal not invoiced yet is dropped, and an unpaid renewal invoice for only this contract is cancelled (by Admins; for other staff an Admin is told).',
    'Check icon (Enable)' => 'Shown on Cancelled and Overdue contracts. Makes the contract Active again, so automatic invoices and reminders start again. It asks first, says no payment is recorded, and says whether a renewal invoice is already open or when the billing run will make one.',
    'Statuses' => 'Active: invoiced and reminded automatically. Overdue: held for non-payment, not invoiced automatically; it turns Active by itself when a renewal invoice for it is paid. Cancelled: not renewed.',
];

GridViewHelper::register($this);

$users = ArrayHelper::map(User::find()->orderBy(['username' => SORT_ASC])->all(), 'id', 'username');
?>

<div class="maintenance-index">
    <div class="card card-outline card-primary shadow-sm">
        <?=
        AdminIndexHelper::pageHeader(
            'Manage AMC contracts and maintenance renewals.',
            'create',
            'Add Maintenance'
        )
        ?>
        <div class="card-body p-0">
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
                        'class' => 'bg-light',
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
                                    $model->websiteHref,
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
                            template: '{view} {update} {toggle-status}',
                            isEnabled: static fn($model): bool => (int) $model->status === \common\models\Maintenance::STATUS_ACTIVE,
                            enableConfirm: \common\services\billing\ServiceEnableNotice::confirm(...),
                        ),
                    ],
                ])
                ?>

        </div>

    </div>

</div>
