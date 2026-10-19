<?php

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\AuditRequest;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\AuditRequestSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Audit Requests';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>

<div class="audit-request-index">
    <div class="card card-outline card-primary shadow-sm">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="text-muted small">
                        Track and process website technical audit
                        and optimisation requests.
                    </div>
                </div>
                <?=
                Html::a(
                        '<i class="fas fa-plus me-1"></i> Add Audit Request',
                        ['create'],
                        ['class' => 'btn btn-primary']
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
                'summaryOptions' => ['class' => 'text-muted small mb-0'],
                'tableOptions' => [
                    'class' => 'table table-hover table-striped align-middle mb-0',
                ],
                'filterRowOptions' => ['class' => 'bg-light'],
                'columns' => [
                    /*
                     * Serial number.
                     */
                    [
                        'class' => 'yii\grid\SerialColumn',
                        'header' => '#',
                        'headerOptions' => ['style' => 'width: 60px;', 'class' => 'text-center'],
                        'contentOptions' => ['class' => 'text-center text-muted'],
                    ],
                    /*
                     * Client name.
                     *
                     * Email is shown through a Bootstrap 5 tooltip
                     * to keep the Grid compact.
                     */
                    [
                        'attribute' => 'name',
                        'label' => 'Client',
                        'format' => 'raw',
                        'value' => static function (AuditRequest $model): string {
                            return Html::tag(
                                    'span',
                                    Html::encode($model->name),
                                    [
                                        'class' => 'fw-semibold text-primary',
                                        'title' => $model->email,
                                        'data-bs-toggle' => 'tooltip',
                                        'data-bs-placement' => 'top',
                                        'style' => 'cursor: help;',
                                    ]
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 140px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Phone.
                     */
                    [
                        'attribute' => 'phone',
                        'value' => static function (AuditRequest $model): string {
                            return $model->phone ?: '-';
                        },
                        'headerOptions' => ['style' => 'min-width: 120px;'],
                    ],
                    /*
                     * Website.
                     */
                    [
                        'attribute' => 'company_url',
                        'format' => 'raw',
                        'value' => static function (AuditRequest $model): string {
                            if (!$model->company_url) {
                                return '<span class="text-muted">-</span>';
                            }
                            return Html::a(
                                    Html::encode($model->company_url),
                                    $model->company_url,
                                    [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                        'class' => 'text-decoration-none',
                                        'title' => $model->company_url,
                                        'data-bs-toggle' => 'tooltip',
                                        'data-bs-placement' => 'top',
                                    ]
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 150px; max-width: 200px;'],
                        'contentOptions' => [
                            'class' => 'text-truncate',
                            'style' => 'max-width: 200px;',
                        ],
                    ],
                    /*
                     * Framework.
                     */
                    [
                        'attribute' => 'current_framework',
                        'value' => static function (AuditRequest $model): string {
                            return $model->current_framework ?: '-';
                        },
                        'headerOptions' => ['style' => 'min-width: 130px;'],
                    ],
                    /*
                     * Status.
                     */
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'filter' => GridFilterHelper::select2($searchModel, 'status', AuditRequest::getStatuses(), 'All Statuses'),
                        'value' => static function (AuditRequest $model): string {
                            $class = match ($model->status) {
                                AuditRequest::STATUS_PENDING => 'bg-warning text-dark',
                                AuditRequest::STATUS_IN_REVIEW => 'bg-info text-dark',
                                AuditRequest::STATUS_CONTACTED => 'bg-primary',
                                AuditRequest::STATUS_CLOSED => 'bg-success',
                                default => 'bg-secondary',
                            };
                            $label = AuditRequest::getStatuses()[$model->status] ?? $model->status;
                            return Html::tag('span', Html::encode($label), ['class' => 'badge ' . $class . ' px-2 py-1']);
                        },
                        'headerOptions' => ['style' => 'min-width: 150px;'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    /*
                     * Created date with UNIX timestamp
                     * date-range filter.
                     */
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created',
                        'filter' => GridFilterHelper::dateRange($searchModel, 'created_at_range', 'Created date'),
                        'value' => static function (AuditRequest $model): string {
                            return Yii::$app->formatter->asDate($model->created_at, 'php:d M Y');
                        },
                        'headerOptions' => ['style' => 'min-width: 190px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Shared Bootstrap 5 CRUD actions.
                     */
                    GridActionHelper::crudColumn(deleteMessage: 'Are you sure you want to delete this audit request?'),
                ],
            ])
            ?>
        </div>
    </div>
</div>