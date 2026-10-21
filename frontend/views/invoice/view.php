<?php
declare(strict_types=1);

use common\models\Invoice;
use yii\helpers\Html;

$this->title = 'Invoice '
        . $model->invoice_number;

$this->params['breadcrumbs'][] = [
    'label' => 'My Invoices',
    'url' => ['index'],
];

$this->params['breadcrumbs'][] = $model->invoice_number;
?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="mb-1">
                <?=
                Html::encode(
                        $model->invoice_number
                )
                ?>
            </h2>

            <span class="text-muted">
                <?=
                Html::encode(
                        Invoice::getStatuses()[
                        $model->status
                        ] ?? $model->status
                )
                ?>
            </span>
        </div>

        <div>
            <?=
            Html::a(
                    'Back to Invoices',
                    ['index'],
                    [
                        'class' =>
                        'btn btn-outline-secondary',
                    ]
            )
            ?>
            <?=
            Html::a(
                    'Download PDF',
                    [
                        'download',
                        'number' =>
                        $model->invoice_number,
                    ],
                    [
                        'class' =>
                        'btn btn-primary',
                    ]
            )
            ?>
            <?php
            if (
                    in_array(
                            $model->status,
                            [
                                Invoice::STATUS_UNPAID,
                                Invoice::STATUS_PARTIALLY_PAID,
                            ],
                            true
                    ) && $outstandingAmount > 0
            ):
                ?>

                <?=
                Html::a(
                        'Submit Payment Proof',
                        [
                            'submit-payment',
                            'number' =>
                            $model->invoice_number,
                        ],
                        [
                            'class' =>
                            'btn btn-success',
                        ]
                )
                ?>

            <?php endif; ?>
        </div>

    </div>
    <?php if ($pendingPayment !== null): ?>

        <div class="alert alert-warning">

            <strong>
                Payment verification pending.
            </strong>

            <div>
                Reference:
                <?=
                Html::encode(
                        $pendingPayment
                        ->gateway_payment_id
                )
                ?>
            </div>

        </div>

    <?php
    elseif (
            in_array(
                    $model->status,
                    [
                        Invoice::STATUS_UNPAID,
                        Invoice::STATUS_PARTIALLY_PAID,
                    ],
                    true
            ) && $outstandingAmount > 0
    ):
        ?>

        <?=
        Html::a(
                'Submit Payment Proof',
                [
                    'submit-payment',
                    'number' =>
                    $model->invoice_number,
                ],
                [
                    'class' =>
                    'btn btn-success',
                ]
        )
        ?>

<?php endif; ?>
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <div class="row mb-4">

                <div class="col-md-6">

                    <h5>
                        Billed To
                    </h5>

                    <div class="fw-semibold">
<?=
Html::encode(
        $model->client_name
)
?>
                    </div>

                    <div>
                        <?=
                        Html::encode(
                                $model
                                ->client_address_line_1
                        )
                        ?>
                    </div>

                    <?php
                    if (
                            $model
                            ->client_address_line_2
                    ):
                        ?>
                        <div>
                            <?=
                            Html::encode(
                                    $model
                                    ->client_address_line_2
                            )
                            ?>
                        </div>
                        <?php endif; ?>

                    <div>
                        <?=
                        Html::encode(
                                implode(
                                        ', ',
                                        array_filter([
                            $model
                            ->client_city,
                            $model
                            ->client_state,
                            $model
                            ->client_postal_code,
                                        ])
                                )
                        )
                        ?>
                    </div>

                </div>

                <div class="col-md-6 text-md-end">

                    <div>
                        <strong>Issued:</strong>

                        <?=
                                Yii::$app
                                ->formatter
                                ->asDate(
                                        $model->created_at,
                                        'php:d M Y'
                                )
                        ?>
                    </div>

                    <div>
                        <strong>Due:</strong>

                        <?=
                                Yii::$app
                                ->formatter
                                ->asDate(
                                        $model->due_date,
                                        'php:d M Y'
                                )
                        ?>
                    </div>

                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">
                                Qty
                            </th>
                            <th class="text-end">
                                Unit Price
                            </th>
                            <th class="text-end">
                                Total
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php
                        foreach (
                                $model->items
                        as $item
                        ):
                            ?>

                            <tr>
                                <td>
    <?=
    Html::encode(
            $item->category
    )
    ?>
                                </td>

                                <td>
    <?=
    Html::encode(
            $item->description
    )
    ?>
                                </td>

                                <td class="text-end">
    <?=
    (int)
    $item->quantity
    ?>
                                </td>

                                <td class="text-end">
                                    <?=
                                            Yii::$app
                                            ->formatter
                                            ->asCurrency(
                                                    $item
                                                    ->unit_price,
                                                    'INR'
                                            )
                                    ?>
                                </td>

                                <td class="text-end">
                                    <?=
                                            Yii::$app
                                            ->formatter
                                            ->asCurrency(
                                                    $item
                                                    ->total_price,
                                                    'INR'
                                            )
                                    ?>
                                </td>
                            </tr>

<?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="row justify-content-end">

                <div class="col-md-5">

                    <table class="table">

                        <tr>
                            <th>Subtotal</th>

                            <td class="text-end">
                                <?=
                                        Yii::$app
                                        ->formatter
                                        ->asCurrency(
                                                $model
                                                ->subtotal_amount,
                                                'INR'
                                        )
                                ?>
                            </td>
                        </tr>

                        <?php
                        if (
                                (float)
                                $model->discount_amount > 0
                        ):
                            ?>

                            <tr>
                                <th>Discount</th>

                                <td class="text-end">
                                    -
                                    <?=
                                            Yii::$app
                                            ->formatter
                                            ->asCurrency(
                                                    $model
                                                    ->discount_amount,
                                                    'INR'
                                            )
                                    ?>
                                </td>
                            </tr>

                                <?php endif; ?>

                        <tr>
                            <th>Total</th>

                            <td class="text-end fw-bold">
                                <?=
                                        Yii::$app
                                        ->formatter
                                        ->asCurrency(
                                                $model
                                                ->total_payable,
                                                'INR'
                                        )
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Received</th>

                            <td class="text-end">
                                <?=
                                        Yii::$app
                                        ->formatter
                                        ->asCurrency(
                                                $capturedAmount,
                                                'INR'
                                        )
                                ?>
                            </td>
                        </tr>

                        <tr>
                            <th>Outstanding</th>

                            <td class="text-end fw-bold">
                                <?=
                                        Yii::$app
                                        ->formatter
                                        ->asCurrency(
                                                $outstandingAmount,
                                                'INR'
                                        )
                                ?>
                            </td>
                        </tr>

                    </table>

                </div>

            </div>

        </div>

    </div>

</div>