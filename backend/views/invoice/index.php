<?php

use common\helpers\GridActionHelper;
use common\helpers\GridFilterHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\Invoice;
use yii\grid\GridView;
use common\helpers\AdminIndexHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var backend\models\searches\InvoiceSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
$this->title = 'Invoices';
$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register($this);
?>

<div class="invoice-index">
    <div class="card card-outline card-primary shadow-sm">
        <!-- Header -->
        <?= AdminIndexHelper::pageHeader('Manage client invoices, payments and due dates.', 'create', 'Create Invoice') ?>

        <!-- Grid -->
        <div class="card-body p-0">
            <?=
            GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'layout' => GridPaginationHelper::gridLayout(),
                'summaryOptions' => ['class' => 'text-muted small mb-0'],
                'tableOptions' => [
                    'class' => 'table table-hover table-striped align-middle mb-0'
                ],
                'filterRowOptions' => ['class' => 'bg-light'],
                'columns' => [
                    /*
                     * Serial number.
                     */
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
                    /*
                     * Invoice Number.
                     */
                    [
                        'attribute' => 'invoice_number',
                        'label' => 'Invoice',
                        'format' => 'raw',
                        'value' => static function (Invoice $model): string {
                            return Html::a(
                                Html::encode($model->invoice_number),
                                ['view', 'number' => $model->invoice_number],
                                ['class' => 'fw-semibold text-decoration-none']
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 150px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Client snapshot.
                     *
                     * invoice.client_name is deliberately displayed
                     * because it represents the invoice-time snapshot.
                     *
                     * Where the related user is available, the current
                     * account email can be shown as additional context
                     * through the tooltip.
                     */
                    [
                        'attribute' => 'client_name',
                        'label' => 'Client',
                        'format' => 'raw',
                        'value' => static function (Invoice $model): string {
                            $options = ['class' => 'fw-semibold text-dark'];
                            if (!empty($model->user->email)) {
                                $options['title'] = $model->user->email;
                                $options['data-bs-toggle'] = 'tooltip';
                                $options['data-bs-placement'] = 'top';
                                $options['style'] = 'cursor: help;';
                            }
                            return Html::tag('span', Html::encode($model->client_name), $options);
                        },
                        'headerOptions' => ['style' => 'min-width: 160px;'],
                    ],
                    /*
                     * Total Payable.
                     */
                    [
                        'attribute' => 'total_payable',
                        'label' => 'Total',
                        'format' => 'raw',
                        'value' => static function (Invoice $model): string {
                            return Html::tag(
                                'span',
                                Yii::$app->formatter->asCurrency($model->total_payable, 'INR'),
                                ['class' => 'fw-semibold']
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 130px;'],
                        'contentOptions' => ['class' => 'text-nowrap text-end'],
                    ],
                    /*
                     * Invoice status.
                     */
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'filter' => GridFilterHelper::select2($searchModel, 'status', Invoice::getStatuses(), 'All Statuses'),
                        'value' => static function (Invoice $model): string {
                            $class = match ($model->status) {
                                Invoice::STATUS_UNPAID => 'bg-danger',
                                Invoice::STATUS_PAID => 'bg-success',
                                Invoice::STATUS_PARTIALLY_PAID => 'bg-warning text-dark',
                                Invoice::STATUS_CARRIED_FORWARD => 'bg-secondary',
                                Invoice::STATUS_CANCELLED => 'bg-dark',
                                Invoice::STATUS_REFUNDED => 'bg-info text-dark',
                                default => 'bg-secondary',
                            };
                            $label = $model->statusLabel();
                            return Html::tag(
                                'span',
                                Html::encode($label),
                                ['class' => 'badge ' . $class . ' px-2 py-1']
                            );
                        },
                        'headerOptions' => ['style' => 'min-width: 155px;'],
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    /*
                     * Payment due date.
                     *
                     * due_date is an SQL DATE column.
                     */
                    [
                        'attribute' => 'due_date',
                        'label' => 'Due Date',
                        'filter' => GridFilterHelper::dateRange($searchModel, 'due_date_range', 'Due dates'),
                        'value' => static function (Invoice $model): string {
                            if (!$model->due_date) {
                                return '-';
                            }
                            return Yii::$app->formatter->asDate($model->due_date, 'php:d M Y');
                        },
                        'headerOptions' => ['style' => 'min-width: 190px;'],
                        'contentOptions' => ['class' => 'text-nowrap'],
                    ],
                    /*
                     * Invoice creation date.
                     *
                     * created_at is an integer UNIX timestamp.
                     */
                    [
                        'attribute' => 'created_at',
                        'label' => 'Created',
                        'filter' => GridFilterHelper::dateRange(
                            $searchModel,
                            'created_at_range',
                            'Created date'
                        ),
                        'value' => static function (
                            Invoice $model
                        ): string {
                            return Yii::$app->formatter
                                    ->asDatetime(
                                        $model->created_at,
                                        'php:d M Y, h:i A'
                                    );
                        },
                        'headerOptions' => [
                            'style' => 'min-width: 190px;',
                        ],
                        'contentOptions' => [
                            'class' => 'text-nowrap',
                        ],
                    ],
                    /*
                     * Shared CRUD actions.
                     */
                    GridActionHelper::crudColumn(
                        template: '{view} {update} {resend-invoice}',
                        urlCreator: static function (string $action, Invoice $model): array {
                            if ($action === 'view') {
                                return ['view', 'number' => $model->invoice_number];
                            }
                                return [$action, 'id' => $model->id];
                        },
                        // Same rule as the Update button on the view page.
                        visibleButtons: [
                            'update' => static fn (Invoice $model): bool => $model->status === Invoice::STATUS_UNPAID,
                            // Same rule as Resend Invoice on the view page.
                            'resend-invoice' => static fn (Invoice $model): bool => in_array($model->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true),
                        ],
                    ),
                ],
            ])
            ?>

        </div>

    </div>

</div>