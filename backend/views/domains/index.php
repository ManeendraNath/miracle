<?php

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Domains;
use common\models\User;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\DomainsSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Domains Registry';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);

/*
 * User list for the Client GridView filter.
 *
 * ID is submitted to DomainsSearch::user_id while the administrator
 * sees a readable username/email instead of the database ID.
 */
$users = ArrayHelper::map(User::find()->orderBy(['username' => SORT_ASC])->all(), 'id', 'username');
?>

<div class="domains-index">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">
                        Manage client domains, registrars,
                        registration dates and expiry dates.
                    </div>
                </div>
                <?=
                Html::a(
                        '<i class="fas fa-plus me-1"></i> Add Domain',
                        ['create'],
                        ['class' => 'btn btn-sm btn-primary']
                )
                ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => ['class' => 'text-muted small mb-3'],
                'tableOptions' => [
                    'class' => 'table table-hover table-striped align-middle mb-0',
                ],
                'filterRowOptions' => ['class' => 'bg-dark'],
                'columns' => [
                    /*
                     * Serial number
                     */
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
                    /*
                     * Client
                     */
                    [
                        'attribute' => 'user_id',
                        'label' => 'Client',
                        'format' => 'raw',
                        'value' => static function (Domains $model): string {
                            if (!$model->user) {
                                return '<span class="text-muted">-</span>';
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
                        'filter' => GridFilterHelper::select2($searchModel, 'user_id', $users, 'All Clients'),
                        'headerOptions' => ['style' => 'min-width: 150px; width: 170px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Domain Name
                     */
                    [
                        'attribute' => 'domain_url',
                        'format' => 'raw',
                        'value' => static function (Domains $model): string {
                            return Html::tag('span', Html::encode($model->domain_url), ['class' => 'fw-semibold text-dark']);
                        },
                        'headerOptions' => ['style' => 'min-width: 200px;'],
                    ],
                    /*
                     * Registrar
                     */
                    [
                        'attribute' => 'registered_date',
                        'filter' => GridFilterHelper::dateRange($searchModel, 'registered_date_range', 'Registration dates'),
                        'format' => ['date', 'php:d M Y'],
                        'headerOptions' => ['style' => 'min-width: 200px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Expiry Date
                     */
                    [
                        'attribute' => 'current_expiry_date',
                        'filter' => GridFilterHelper::dateRange($searchModel, 'current_expiry_date_range', 'Expiry dates'),
                        'format' => ['date', 'php:d M Y'],
                        'headerOptions' => ['style' => 'min-width: 200px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Status
                     */
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'filter' => GridFilterHelper::select2($searchModel, 'status', Domains::getStatuses(), 'All Statuses'),
                        'value' => static function (Domains $model): string {
                            $statuses = Domains::getStatuses();
                            $label = $statuses[$model->status] ?? 'Unknown';
                            $class = match ((int) $model->status) {
                                Domains::STATUS_ACTIVE => 'bg-success',
                                Domains::STATUS_INACTIVE => 'bg-secondary',
                                Domains::STATUS_DELETED => 'bg-danger',
                                default => 'bg-dark',
                            };
                            return Html::tag('span', Html::encode($label), ['class' => 'badge ' . $class]);
                        },
                        'headerOptions' => ['style' => 'width: 150px;'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    /*
                     * CRUD actions
                     */
                    GridActionHelper::crudColumn(deleteMessage: 'Are you sure you want to delete this domain?'),
                ],
            ])
            ?>
        </div>
    </div>
</div>
