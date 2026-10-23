<?php
declare(strict_types=1);

use common\models\Invoice;
use common\models\InvoiceItem;
use yii\helpers\Html;

$subtotal = round((float) $model->subtotal_amount, 2);
$discount = round((float) $model->discount_amount, 2);
$taxableAmount = max(0, round($subtotal - $discount, 2));
$cgstAmount = round($taxableAmount * ((float) $model->cgst_percent / 100), 2);
$sgstAmount = round($taxableAmount * ((float) $model->sgst_percent / 100), 2);
$igstAmount = round($taxableAmount * ((float) $model->igst_percent / 100), 2);
$totalTax = round($cgstAmount + $sgstAmount + $igstAmount, 2);
$money = static function (int|float|string|null $amount): string {
    return Yii::$app->formatter->asCurrency((float) ($amount ?? 0), 'INR');
};
?>
<!-- Main Printable Invoice Canvas Base Area -->
<div class="invoice p-5 bg-white border rounded shadow-sm position-relative">

    <!-- Watermark Status Tag Badge Overlay -->
    <div class="position-absolute" style="top: 40px; right: 50px;">
        <?php
        $statusClass = match ($model->status) {
            Invoice::STATUS_PAID => 'bg-success',
            Invoice::STATUS_UNPAID => 'bg-danger',
            Invoice::STATUS_PARTIALLY_PAID => 'bg-warning text-dark',
            Invoice::STATUS_CARRIED_FORWARD => 'bg-secondary',
            Invoice::STATUS_CANCELLED => 'bg-dark',
            Invoice::STATUS_REFUNDED => 'bg-info text-dark',
            default => 'bg-secondary',
        };
        ?>
        <span class="badge <?= $statusClass ?> p-2">
            <?= Html::encode(Invoice::getStatuses()[$model->status] ?? $model->status) ?>
        </span>
    </div>

    <!-- 🏢 Brand Heading & Company Metadata Row -->
    <div class="row align-items-center mb-5">
        <div class="col-md-6">
            <h2 class="text-primary fw-bold m-0" style="letter-spacing: -0.5px;">
                <i class="fas fa-cubes me-2"></i>MIRACLE WEB TECHNOLOGIES
            </h2>
            <p class="text-muted text-sm mt-1 mb-0">
                Premium Enterprise Engineering & Systems Architectures<br>
                Support Line: admin@miraclewebtechnologies.com
            </p>
        </div>
        <div class="col-md-6 text-end pt-4">
            <h1 class="text-uppercase fw-light text-muted m-0" style="font-size: 2.5rem;">INVOICE</h1>
            <p class="text-dark fw-bold m-0">Reference ID: <span class="text-primary"><?= Html::encode($model->invoice_number) ?></span></p>
        </div>
    </div>

    <hr class="my-4">

    <!-- 👥 Billing Addresses Columns -->
    <div class="row mb-5">
        <div class="col-6">
            <p class="text-uppercase fw-bold text-muted text-xs mb-1">Invoiced To:</p>
            <h5 class="fw-bold text-dark mb-1"><?= Html::encode($model->client_name) ?></h5>
            <p class="text-secondary text-sm mb-0">
                <?= Html::encode($model->client_address_line_1) ?><br>
                <?= Html::encode($model->client_address_line_2) ?>
            </p>
        </div>
        <div class="col-6 text-end">
            <p class="text-uppercase fw-bold text-muted text-xs mb-1">Timeline Dates:</p>
            <p class="text-sm text-dark mb-1"><strong>Issue Date:</strong> <?=
                Yii::$app->formatter->asDate(
                        $model->created_at,
                        'php:d M Y'
                )
                ?></p>
            <p class="text-sm text-danger mb-0"><strong>Due Date:</strong><?=
                $model->due_date ? Yii::$app->formatter->asDate(
                                $model->due_date,
                                'php:d M Y'
                        ) : 'Upon Receipt'
                ?></p>
        </div>
    </div>

    <!-- 📊 Transaction Ledger Table Sheet -->
    <?php
    $items = $model->items;
    ?>

    <div class="table-responsive my-4">

        <table class="table table-striped align-middle">

            <thead>

                <tr class="bg-primary text-white">

                    <th style="width: 18%">
                        Category
                    </th>

                    <th>
                        Description
                    </th>

                    <th
                        class="text-center"
                        style="width: 10%"
                        >
                        Qty
                    </th>

                    <th
                        class="text-end"
                        style="width: 15%"
                        >
                        Unit Price
                    </th>

                    <th
                        class="text-end"
                        style="width: 15%"
                        >
                        Total
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php if ($items): ?>

                    <?php foreach ($items as $item): ?>

                        <tr>

                            <td class="fw-semibold">

                                <?=
                                Html::encode(
                                        $item->category
                                )
                                ?>

                                <?php
                                if (
                                        (int) $item->item_type === InvoiceItem::TYPE_ARREARS
                                ):
                                    ?>

                                    <div>
                                        <span class="badge bg-warning text-dark">
                                            Previous Balance
                                        </span>
                                    </div>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?=
                                nl2br(
                                        Html::encode(
                                                $item->description
                                        )
                                )
                                ?>
                            </td>

                            <td class="text-center">
                                <?= (int) $item->quantity ?>
                            </td>

                            <td class="text-end text-nowrap">
                                <?= $money($item->unit_price) ?>
                            </td>

                            <td class="text-end fw-semibold text-nowrap">
                                <?= $money($item->total_price) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            No invoice items are available.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <!-- 🧮 Summary Computation Blocks Row -->
    <div class="row justify-content-end mt-5">
        <div class="col-md-5">
            <div class="table-responsive">
                <table class="table table-sm table-borderless text-sm text-dark">
                    <tr>
                        <td>
                            Subtotal
                        </td>

                        <td class="text-end">
                            <?=
                            $money(
                                    $subtotal
                            )
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Discount
                        </td>

                        <td class="text-end">
                            -
                            <?=
                            $money(
                                    $discount
                            )
                            ?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            Taxable Amount
                        </td>

                        <td class="text-end">
                            <?=
                            $money(
                                    $taxableAmount
                            )
                            ?>
                        </td>
                    </tr>

                    <?php
                    if (
                            (float) $model->cgst_percent > 0
                    ):
                        ?>

                        <tr>
                            <td>
                                CGST
                                (<?=
                                number_format(
                                        (float)
                                        $model->cgst_percent,
                                        2
                                )
                                ?>%)
                            </td>

                            <td class="text-end">
                                <?=
                                $money(
                                        $cgstAmount
                                )
                                ?>
                            </td>
                        </tr>

                    <?php endif; ?>

                    <?php
                    if (
                            (float) $model->sgst_percent > 0
                    ):
                        ?>

                        <tr>
                            <td>
                                SGST
                                (<?=
                                number_format(
                                        (float)
                                        $model->sgst_percent,
                                        2
                                )
                                ?>%)
                            </td>

                            <td class="text-end">
                                <?= $money($sgstAmount) ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ((float) $model->igst_percent > 0): ?>
                        <tr>
                            <td>
                                IGST (<?= number_format((float) $model->igst_percent, 2) ?>%)
                            </td>
                            <td class="text-end">
                                <?= $money($igstAmount) ?>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr class="fw-bold">
                        <td>Grand Total</td>
                        <td class="text-end">
                            <?= $money((float) $model->total_payable) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Payments Received</td>
                        <td class="text-end">
                            <?= $money((float) $capturedAmount) ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Outstanding</td>
                        <td class="text-end">
                            <?= $money((float) $outstandingAmount) ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <hr class="my-5">
    <div class="text-center text-muted text-xs">
        <p class="m-0">Thank you for your business with Miracle Web Technologies.</p>
        <p class="m-0 text-xxs mt-1">This is an automated system generated billing accounting summary document. No physical signature parameters are required.</p>
    </div>
</div>