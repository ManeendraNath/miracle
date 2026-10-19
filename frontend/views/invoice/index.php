<?php
declare(strict_types=1);

use common\models\Invoice;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'My Invoices';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-4">

    <div class="mb-4">
        <h2 class="mb-1">
            My Invoices
        </h2>

        <div class="text-muted">
            Review your invoices, balances and payment status.
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="card-body p-0">

            <?=
            GridView::widget([
                'dataProvider' =>
                $dataProvider,
                'tableOptions' => [
                    'class' =>
                    'table table-hover align-middle mb-0',
                ],
                'columns' => [
                    [
                        'attribute' =>
                        'invoice_number',
                        'label' =>
                        'Invoice',
                        'format' =>
                        'raw',
                        'value' =>
                        static function (
                                Invoice $model
                        ): string {
                            return Html::a(
                                    Html::encode(
                                            $model
                                            ->invoice_number
                                    ),
                                    [
                                        'view',
                                        'number' =>
                                        $model
                                        ->invoice_number,
                                    ],
                                    [
                                        'class' =>
                                        'fw-semibold text-decoration-none',
                                    ]
                            );
                        },
                    ],
                    [
                        'attribute' =>
                        'created_at',
                        'label' =>
                        'Issued',
                        'format' =>
                        [
                            'date',
                            'php:d M Y',
                        ],
                    ],
                    [
                        'attribute' =>
                        'due_date',
                        'label' =>
                        'Due',
                        'format' =>
                        [
                            'date',
                            'php:d M Y',
                        ],
                    ],
                    [
                        'attribute' =>
                        'total_payable',
                        'label' =>
                        'Total',
                        'value' =>
                        static function (
                                Invoice $model
                        ): string {
                            return Yii::$app
                                    ->formatter
                                    ->asCurrency(
                                            $model
                                            ->total_payable,
                                            'INR'
                                    );
                        },
                    ],
                    [
                        'attribute' =>
                        'status',
                        'label' =>
                        'Status',
                        'format' =>
                        'raw',
                        'value' =>
                        static function (
                                Invoice $model
                        ): string {
                            $class = match (
                            $model->status
                            ) {
                                Invoice::STATUS_PAID =>
                                'bg-success',
                                Invoice::STATUS_PARTIALLY_PAID =>
                                'bg-warning text-dark',
                                Invoice::STATUS_UNPAID =>
                                'bg-danger',
                                Invoice::STATUS_CARRIED_FORWARD =>
                                'bg-secondary',
                                Invoice::STATUS_CANCELLED =>
                                'bg-dark',
                                default =>
                                'bg-secondary',
                            };

                            $label = Invoice::getStatuses()[
                                    $model->status
                                    ] ?? $model->status;

                            return Html::tag(
                                    'span',
                                    Html::encode(
                                            $label
                                    ),
                                    [
                                        'class' =>
                                        'badge '
                                        . $class,
                                    ]
                            );
                        },
                    ],
                ],
            ])
            ?>

        </div>

    </div>

</div>