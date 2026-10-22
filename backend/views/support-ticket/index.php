<?php
declare(strict_types=1);

use common\helpers\GridActionHelper;
use common\helpers\GridPaginationHelper;
use common\helpers\GridViewHelper;
use common\models\SupportTicket;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Support Requests';

$this->params['breadcrumbs'][] = $this->title;

GridViewHelper::register(
        $this
);
?>

<div class="support-ticket-index">

    <div class="
         d-flex
         justify-content-between
         align-items-center
         mb-3
         ">

        <div>

            <h1 class="h3 mb-1">
                Support Requests
            </h1>

            <div class="text-muted">
                Review customer support conversations
                and manage request status.
            </div>

        </div>

    </div>

    <div class="card shadow-sm">

        <div class="card-body p-0">

<?=
GridView::widget([
    'dataProvider' =>
    $dataProvider,
    'filterModel' =>
    $searchModel,
    'layout' =>
    GridPaginationHelper::gridLayout(),
    'tableOptions' => [
        'class' =>
        'table table-hover '
        . 'table-striped '
        . 'align-middle mb-0',
    ],
    'filterRowOptions' => [
        'class' =>
        'bg-light',
    ],
    'columns' => [
        [
            'class' =>
            'yii\grid\SerialColumn',
            'header' =>
            '#',
            'headerOptions' => [
                'style' =>
                'width:60px;',
                'class' =>
                'text-center',
            ],
            'contentOptions' => [
                'class' =>
                'text-center text-muted',
            ],
        ],
        [
            'attribute' =>
            'ticket_number',
            'label' =>
            'Ticket',
            'format' =>
            'raw',
            'value' =>
            static function (
                    SupportTicket $model
            ): string {
                return Html::a(
                        Html::encode(
                                $model
                                ->ticket_number
                        ),
                        [
                            'view',
                            'number' =>
                            $model
                            ->ticket_number,
                        ],
                        [
                            'class' =>
                            'fw-semibold '
                            . 'text-decoration-none',
                        ]
                );
            },
            'contentOptions' => [
                'class' =>
                'text-nowrap',
            ],
        ],
        [
            'label' =>
            'Customer',
            'format' =>
            'raw',
            'value' =>
            static function (
                    SupportTicket $model
            ): string {
                if (
                        $model->user === null
                ) {
                    return '-';
                }

                return Html::tag(
                        'span',
                        Html::encode(
                                $model->user
                                ->username
                        ),
                        [
                            'title' =>
                            $model->user
                            ->email,
                            'data-bs-toggle' =>
                            'tooltip',
                            'data-bs-placement' =>
                            'top',
                            'class' =>
                            'fw-semibold',
                        ]
                );
            },
        ],
        [
            'attribute' =>
            'subject',
            'contentOptions' => [
                'style' =>
                'max-width:300px;'
                . 'white-space:normal;',
            ],
        ],
        [
            'attribute' =>
            'priority',
            'filter' =>
            SupportTicket
            ::getPriorities(),
            'format' =>
            'raw',
            'value' =>
            static function (
                    SupportTicket $model
            ): string {
                $class = match (
                $model->priority
                ) {
                    SupportTicket::PRIORITY_HIGH =>
                    'bg-danger',
                    SupportTicket::PRIORITY_LOW =>
                    'bg-secondary',
                    default =>
                    'bg-info text-dark',
                };

                return Html::tag(
                        'span',
                        Html::encode(
                                $model->priority
                        ),
                        [
                            'class' =>
                            'badge '
                            . $class,
                        ]
                );
            },
        ],
        [
            'attribute' =>
            'status',
            'filter' =>
            SupportTicket
            ::getStatuses(),
            'format' =>
            'raw',
            'value' =>
            static function (
                    SupportTicket $model
            ): string {
                $class = match (
                $model->status
                ) {
                    SupportTicket::STATUS_OPEN =>
                    'bg-primary',
                    SupportTicket::STATUS_IN_PROGRESS =>
                    'bg-warning text-dark',
                    SupportTicket::STATUS_RESOLVED =>
                    'bg-success',
                    SupportTicket::STATUS_CLOSED =>
                    'bg-secondary',
                    default =>
                    'bg-secondary',
                };

                return Html::tag(
                        'span',
                        Html::encode(
                                $model->status
                        ),
                        [
                            'class' =>
                            'badge '
                            . $class,
                        ]
                );
            },
        ],
        [
            'attribute' =>
            'updated_at',
            'label' =>
            'Last Updated',
            'format' => [
                'datetime',
                'php:d M Y, h:i A',
            ],
            'contentOptions' => [
                'class' =>
                'text-nowrap',
            ],
        ],
        GridActionHelper::crudColumn(
                template: '{view}',
                urlCreator:
                static function (
                        string $action,
                        SupportTicket $model
                ): array {
                    return [
                'view',
                'number' =>
                $model
                ->ticket_number,
                    ];
                }
        ),
    ],
])
?>

        </div>

    </div>

</div>