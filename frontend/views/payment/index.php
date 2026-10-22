<?php
declare(strict_types=1);

use common\models\Transaction;
use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Payment History';

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container py-4">

    <div class="mb-4">
        <h2 class="mb-1">
            Payment History
        </h2>

        <div class="text-muted">
            Review submitted, verified and completed payments.
        </div>
    </div>

    <div class="card shadow-sm">

        <div class="table-responsive">

            <?=
            GridView::widget([
                'dataProvider' =>
                $dataProvider,
                'layout' =>
                "{items}\n{pager}",
                'tableOptions' => [
                    'class' =>
                    'table table-hover align-middle mb-0',
                ],
                'columns' => [
                    [
                        'attribute' =>
                        'invoice_id',
                        'label' =>
                        'Invoice',
                        'format' =>
                        'raw',
                        'value' =>
                        static function (
                                Transaction $model
                        ): string {
                            if (
                                    $model->invoice === null
                            ) {
                                return '-';
                            }

                            return Html::a(
                                    Html::encode(
                                            $model->invoice
                                            ->invoice_number
                                    ),
                                    [
                                        '/invoice/view',
                                        'number' =>
                                        $model->invoice
                                        ->invoice_number,
                                    ]
                            );
                        },
                    ],
                    [
                        'attribute' =>
                        'gateway_payment_id',
                        'label' =>
                        'Reference',
                    ],
                    [
                        'attribute' =>
                        'amount',
                        'label' =>
                        'Amount',
                        'value' =>
                        static fn(
                        Transaction $model
                        ): string =>
                                Yii::$app
                                ->formatter
                                ->asCurrency(
                                        $model->amount,
                                        'INR'
                                ),
                    ],
                    [
                        'attribute' =>
                        'status',
                        'format' =>
                        'raw',
                        'value' =>
                        static function (
                                Transaction $model
                        ): string {
                            $class = match (
                            $model->status
                            ) {
                                Transaction::STATUS_CAPTURED =>
                                'bg-success',
                                Transaction::STATUS_PENDING =>
                                'bg-warning text-dark',
                                Transaction::STATUS_FAILED =>
                                'bg-danger',
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
                        'created_at',
                        'label' =>
                        'Submitted',
                        'format' => [
                            'datetime',
                            'php:d M Y, h:i A',
                        ],
                    ],
                    [
                        'attribute' =>
                        'paid_at',
                        'label' =>
                        'Confirmed',
                        'value' =>
                        static fn(
                        Transaction $model
                        ): string =>
                        $model->paid_at ? Yii::$app
                                        ->formatter
                                        ->asDatetime(
                                                $model->paid_at,
                                                'php:d M Y, h:i A'
                                        ) : '-',
                    ],
                ],
            ])
            ?>
        </div>
    </div>
</div>