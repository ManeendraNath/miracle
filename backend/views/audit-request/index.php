<?php

use backend\controllers\BaseController;
use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\AuditRequest;
use yii\grid\GridView;
use common\helpers\AdminIndexHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\AuditRequestSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Audit Requests';
$this->params['breadcrumbs'][] = $this->title;
$this->params['help'] = [
    'Where these come from' => 'The form on the public "Website upgrades" page. Each new one emails the Primary Helpdesk Inbound Email from Site Settings and alerts staff.',
    'Eye icon' => 'Open the request: client details, website, framework, hosting and their message. Hover a client name in the list to see their email.',
    'Pencil icon' => 'Edit the request or change its status (Admins only).',
    'Trash icon' => 'Delete the request for good (Admins only).',
    'Add Audit Request' => 'Record a request that came in another way, such as by phone or email (Admins only).',
    'Statuses' => 'Pending (new), In Review, Contacted, Closed. They are for your own tracking: changing one sends nothing to the client.',
    'To reply' => 'The client gets no automatic answer. Open the request and click their email address to write to them.',
];

GridViewHelper::register($this);
?>

<div class="audit-request-index">
    <div class="card card-outline card-primary shadow-sm">
        <?= \backend\controllers\BaseController::canAccess(Yii::$app->user->identity, 'audit-request', 'create')
            ? AdminIndexHelper::pageHeader('Track and process website technical audit and optimisation requests.', 'create', 'Add Audit Request')
            : AdminIndexHelper::pageHeader('Track and process website technical audit and optimisation requests.') ?>
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
                            return Yii::$app->formatter->asDatetime($model->created_at, 'php:d M Y, h:i A');
                        },
                        'headerOptions' => ['style' => 'min-width: 190px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Shared Bootstrap 5 CRUD actions.
                     */
                    GridActionHelper::crudColumn(
                        deleteMessage: 'Are you sure you want to delete this audit request?',
                        // Managers only view audit requests (BaseController::MIN_ROLE).
                        visibleButtons: [
                            'update' => BaseController::canAccess(Yii::$app->user->identity, 'audit-request', 'update'),
                            'delete' => BaseController::canAccess(Yii::$app->user->identity, 'audit-request', 'delete'),
                        ],
                    ),
                ],
            ])
            ?>
        </div>
    </div>
</div>