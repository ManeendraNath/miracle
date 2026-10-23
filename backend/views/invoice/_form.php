<?php

use kartik\select2\Select2;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var common\models\Invoice $model */
/** @var backend\models\forms\ManualInvoiceItemForm[] $items */
/** @var array $usersList */
$isNew = $model->isNewRecord;
?>

<div class="invoice-form">

    <?php
    $form = ActiveForm::begin([
        'id' => 'invoice-form',
    ]);
    ?>

    <div class="card card-outline card-primary shadow-sm">

        <div class="card-header">

            <h3 class="card-title mb-0">
                <?=
                $isNew ? 'Create Manual Invoice' : 'Update Manual Invoice'
                ?>
            </h3>

        </div>


        <div class="card-body">

            <?=
            $form->errorSummary(
                    $model,
                    [
                        'class' =>
                        'alert alert-danger',
                    ]
            )
            ?>


            <h6 class="text-primary border-bottom pb-2 mb-3">
                Client
            </h6>


            <div class="row">

                <div class="col-md-6">

                    <?php if ($isNew): ?>

                        <?=
                                $form
                                ->field(
                                        $model,
                                        'user_id'
                                )
                                ->widget(
                                        Select2::class,
                                        [
                                            'data' =>
                                            $usersList,
                                            'options' => [
                                                'placeholder' =>
                                                'Select Client',
                                            ],
                                            'pluginOptions' => [
                                                'allowClear' =>
                                                false,
                                                'width' =>
                                                '100%',
                                            ],
                                        ]
                                )
                        ?>

                    <?php else: ?>

                        <label class="form-label">
                            Client
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            value="<?=
                            Html::encode(
                                    $model->client_name
                            )
                            ?>"
                            readonly
                            >

                    <?php endif; ?>

                </div>


                <div class="col-md-6">

                    <?=
                            $form
                            ->field(
                                    $model,
                                    'due_date'
                            )
                            ->input('date')
                    ?>

                </div>

            </div>


            <h6 class="text-primary border-bottom pb-2 mt-4 mb-3">
                Invoice Items
            </h6>


            <div class="table-responsive">

                <table
                    class="table table-bordered align-middle"
                    id="manual-items-table"
                    >

                    <thead class="table-light">

                        <tr>

                            <th style="width: 18%">
                                Category
                            </th>

                            <th>
                                Description
                            </th>

                            <th style="width: 10%">
                                Qty
                            </th>

                            <th style="width: 15%">
                                Unit Price
                            </th>

                            <th style="width: 15%">
                                Total
                            </th>

                            <th style="width: 55px"></th>

                        </tr>

                    </thead>


                    <tbody id="invoice-item-rows">

                        <?php
                        foreach (
                                $items as $index => $item
                        ):
                            ?>

                            <tr class="invoice-item-row">

                                <td>

                                    <input
                                        type="text"
                                        name="ManualInvoiceItemForm[<?= $index ?>][category]"
                                        value="<?=
                                        Html::encode(
                                                $item->category
                                        )
                                        ?>"
                                        class="form-control"
                                        maxlength="100"
                                        required
                                        >

                                </td>


                                <td>

                                    <input
                                        type="text"
                                        name="ManualInvoiceItemForm[<?= $index ?>][description]"
                                        value="<?=
                                        Html::encode(
                                                $item->description
                                        )
                                        ?>"
                                        class="form-control"
                                        maxlength="500"
                                        required
                                        >

                                </td>


                                <td>

                                    <input
                                        type="number"
                                        name="ManualInvoiceItemForm[<?= $index ?>][quantity]"
                                        value="<?=
                                        Html::encode(
                                                $item->quantity
                                        )
                                        ?>"
                                        class="form-control invoice-item-qty"
                                        min="1"
                                        step="1"
                                        required
                                        >

                                </td>


                                <td>

                                    <input
                                        type="number"
                                        name="ManualInvoiceItemForm[<?= $index ?>][unit_price]"
                                        value="<?=
                                        Html::encode(
                                                $item->unit_price
                                        )
                                        ?>"
                                        class="form-control invoice-item-price"
                                        min="0"
                                        step="0.01"
                                        required
                                        >

                                </td>


                                <td>

                                    <input
                                        type="text"
                                        class="form-control invoice-item-total"
                                        value="<?=
                                        number_format(
                                                $item->getTotal(),
                                                2,
                                                '.',
                                                ''
                                        )
                                        ?>"
                                        readonly
                                        >

                                </td>


                                <td class="text-center">

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger remove-item"
                                        title="Remove item"
                                        >
                                        <i class="fas fa-times"></i>
                                    </button>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>


            <button
                type="button"
                id="add-invoice-item"
                class="btn btn-outline-primary btn-sm"
                >
                <i class="fas fa-plus me-1"></i>
                Add Item
            </button>


            <h6 class="text-primary border-bottom pb-2 mt-4 mb-3">
                Invoice Summary
            </h6>


            <div class="row justify-content-end">

                <div class="col-lg-5">

                    <div class="mb-3">

                        <label class="form-label">
                            Subtotal
                        </label>

                        <input
                            type="text"
                            id="invoice-subtotal-preview"
                            class="form-control"
                            readonly
                            >

                    </div>


                    <?=
                    $form->field(
                            $model,
                            'discount_amount'
                    )->textInput([
                        'type' => 'number',
                        'min' => 0,
                        'step' => '0.01',
                        'id' => 'invoice-discount',
                    ])->hint(
                            'Manual discount. If a coupon code is supplied, the validated coupon discount will override this value.'
                    )
                    ?>


                    <?=
                    $form->field(
                            $model,
                            'coupon_code'
                    )->textInput([
                        'maxlength' => true,
                        'autocomplete' => 'off',
                        'style' => 'text-transform: uppercase;',
                        'placeholder' => 'Optional coupon code',
                    ])->hint(
                            'Coupon eligibility and final discount are validated when the invoice is saved.'
                    )
                    ?>



                    <div class="row">

                        <div class="col-4">

                            <?=
                                    $form
                                    ->field(
                                            $model,
                                            'cgst_percent'
                                    )
                                    ->textInput([
                                        'readonly' => true,
                                        'id' =>
                                        'invoice-cgst',
                                    ])
                            ?>

                        </div>

                        <div class="col-4">

                            <?=
                                    $form
                                    ->field(
                                            $model,
                                            'sgst_percent'
                                    )
                                    ->textInput([
                                        'readonly' => true,
                                        'id' =>
                                        'invoice-sgst',
                                    ])
                            ?>

                        </div>

                        <div class="col-4">

                            <?=
                                    $form
                                    ->field(
                                            $model,
                                            'igst_percent'
                                    )
                                    ->textInput([
                                        'readonly' => true,
                                        'id' =>
                                        'invoice-igst',
                                    ])
                            ?>

                        </div>

                    </div>


                    <div class="mb-3">

                        <label class="form-label fw-bold">
                            Total Payable
                        </label>

                        <input
                            type="text"
                            id="invoice-total-preview"
                            class="form-control fw-bold text-success"
                            readonly
                            >

                    </div>

                </div>

            </div>

        </div>


        <div
            class="card-footer d-flex justify-content-between"
            >

            <?=
            Html::a(
                    'Cancel',
                    $isNew ? ['index'] : [
                        'view',
                        'number' =>
                        $model->invoice_number,
                            ],
                    [
                        'class' =>
                        'btn btn-outline-secondary',
                    ]
            )
            ?>

            <?=
            Html::submitButton(
                    $isNew ? 'Create Invoice' : 'Update Invoice',
                    [
                        'class' =>
                        'btn btn-primary px-4',
                    ]
            )
            ?>

        </div>

    </div>

<?php ActiveForm::end(); ?>

</div>
<?php
$nextIndex = count($items);

$js = <<<JS

let manualItemIndex = {$nextIndex};

function recalculateManualInvoice() {

    let subtotal = 0;

    $('.invoice-item-row').each(function () {

        const qty =
            parseFloat(
                $(this)
                    .find('.invoice-item-qty')
                    .val()
            ) || 0;

        const price =
            parseFloat(
                $(this)
                    .find('.invoice-item-price')
                    .val()
            ) || 0;

        const total =
            qty * price;

        $(this)
            .find('.invoice-item-total')
            .val(
                total.toFixed(2)
            );

        subtotal += total;
    });


    const discount =
        parseFloat(
            $('#invoice-discount').val()
        ) || 0;


    const taxable =
        Math.max(
            0,
            subtotal - discount
        );


    const cgst =
        parseFloat(
            $('#invoice-cgst').val()
        ) || 0;

    const sgst =
        parseFloat(
            $('#invoice-sgst').val()
        ) || 0;

    const igst =
        parseFloat(
            $('#invoice-igst').val()
        ) || 0;


    const tax =
        taxable
        * (
            (cgst + sgst + igst)
            / 100
        );


    $('#invoice-subtotal-preview')
        .val(
            subtotal.toFixed(2)
        );


    $('#invoice-total-preview')
        .val(
            (taxable + tax).toFixed(2)
        );
}


$('#add-invoice-item').on(
    'click',
    function () {

        const index =
            manualItemIndex++;

        const row = `
<tr class="invoice-item-row">

    <td>
        <input
            type="text"
            name="ManualInvoiceItemForm[\${index}][category]"
            class="form-control"
            maxlength="100"
            required
        >
    </td>

    <td>
        <input
            type="text"
            name="ManualInvoiceItemForm[\${index}][description]"
            class="form-control"
            maxlength="500"
            required
        >
    </td>

    <td>
        <input
            type="number"
            name="ManualInvoiceItemForm[\${index}][quantity]"
            class="form-control invoice-item-qty"
            value="1"
            min="1"
            step="1"
            required
        >
    </td>

    <td>
        <input
            type="number"
            name="ManualInvoiceItemForm[\${index}][unit_price]"
            class="form-control invoice-item-price"
            value="0.00"
            min="0"
            step="0.01"
            required
        >
    </td>

    <td>
        <input
            type="text"
            value="0.00"
            class="form-control invoice-item-total"
            readonly
        >
    </td>

    <td class="text-center">

        <button
            type="button"
            class="btn btn-sm btn-outline-danger remove-item"
        >
            <i class="fas fa-times"></i>
        </button>

    </td>

</tr>`;

        $('#invoice-item-rows')
            .append(row);

        recalculateManualInvoice();
    }
);


$(document).on(
    'click',
    '.remove-item',
    function () {

        /*
         * Keep at least one editor row.
         */
        if (
            $('.invoice-item-row').length <= 1
        ) {
            return;
        }

        $(this)
            .closest('.invoice-item-row')
            .remove();

        recalculateManualInvoice();
    }
);


$(document).on(
    'input change',
    '.invoice-item-qty, '
    + '.invoice-item-price, '
    + '#invoice-discount',
    recalculateManualInvoice
);


recalculateManualInvoice();

JS;

$this->registerJs($js);
?>