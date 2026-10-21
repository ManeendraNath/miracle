<?php
declare(strict_types=1);

use yii\helpers\Html;

$money = static function (float $amount): string {
    return 'INR ' . number_format($amount, 2);
};
$payment = Yii::$app->params['billing']['payment'] ?? [];
$taxableAmount = max(0, round((float) $invoice->subtotal_amount - (float) $invoice->discount_amount, 2));
$cgstAmount = round($taxableAmount * ((float) $invoice->cgst_percent / 100), 2);
$sgstAmount = round($taxableAmount * ((float) $invoice->sgst_percent / 100), 2);
$igstAmount = round($taxableAmount * ((float) $invoice->igst_percent / 100), 2);
?>

<style>
    body {
        font-family: sans-serif;
        font-size: 10pt;
        color: #1f2937;
    }

    .header {
        border-bottom: 2px solid #0f172a;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }

    .company {
        font-size: 18pt;
        font-weight: bold;
    }

    .invoice-title {
        font-size: 20pt;
        font-weight: bold;
        text-align: right;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .items th {
        background: #f1f5f9;
        padding: 8px;
        border: 1px solid #cbd5e1;
    }

    .items td {
        padding: 8px;
        border: 1px solid #cbd5e1;
    }

    .right {
        text-align: right;
    }

    .summary {
        margin-top: 20px;
    }

    .summary td {
        padding: 5px;
    }

    .total {
        font-size: 12pt;
        font-weight: bold;
    }
    .payment-box {
        margin-top: 28px;
        border: 1px solid #cbd5e1;
        padding: 12px;
        background: #f8fafc;
    }

    .payment-title {
        font-size: 12pt;
        font-weight: bold;
        margin-bottom: 8px;
        color: #0f172a;
    }

    .payment-details td {
        padding: 3px 6px;
    }

    .payment-label {
        font-weight: bold;
        width: 32%;
    }

    .payment-note {
        margin-top: 10px;
        font-size: 9pt;
        color: #475569;
    }

</style>

<div class="header">
    <table>
        <tr>
            <td>
                <div class="company">Miracle Web Technologies</div>

                <div>
                    Delhi, India
                </div>
            </td>

            <td class="invoice-title">
                INVOICE
            </td>
        </tr>
    </table>
</div>

<table>
    <tr>
        <td width="55%">
            <strong>Billed To</strong><br>

            <?=
            Html::encode(
                    $invoice->client_name
            )
            ?><br>

            <?=
            Html::encode(
                    $invoice
                    ->client_address_line_1
            )
            ?><br>

            <?php
            if (
                    $invoice
                    ->client_address_line_2
            ):
                ?>

                <?=
                Html::encode(
                        $invoice
                        ->client_address_line_2
                )
                ?><br>

            <?php endif; ?>

            <?=
            Html::encode(
                    implode(
                            ', ',
                            array_filter([
                $invoice->client_city,
                $invoice->client_state,
                $invoice
                ->client_postal_code,
                            ])
                    )
            )
            ?>
        </td>

        <td>
            <strong>Invoice:</strong>
            <?=
            Html::encode(
                    $invoice->invoice_number
            )
            ?><br>

            <strong>Issue Date:</strong>
            <?=
            date(
                    'd M Y',
                    (int) $invoice->created_at
            )
            ?><br>

            <strong>Due Date:</strong>
            <?=
            Html::encode(
                    (string)
                    $invoice->due_date
            )
            ?>
        </td>
    </tr>
</table>

<br>

<table class="items">
    <thead>
        <tr>
            <th>Category</th>
            <th>Description</th>
            <th class="right">Qty</th>
            <th class="right">Rate</th>
            <th class="right">Amount</th>
        </tr>
    </thead>

    <tbody>

        <?php
        foreach (
                $invoice->items as $item
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

                <td class="right">
                    <?=
                    (int)
                    $item->quantity
                    ?>
                </td>

                <td class="right">
                    <?=
                    $money(
                            (float)
                            $item->unit_price
                    )
                    ?>
                </td>

                <td class="right">
                    <?=
                    $money(
                            (float)
                            $item->total_price
                    )
                    ?>
                </td>
            </tr>

        <?php endforeach; ?>

    </tbody>
</table>

<table class="summary">

    <tr>
        <td width="60%"></td>

        <td>
            Subtotal
        </td>

        <td class="right">
            <?=
            $money(
                    (float)
                    $invoice->subtotal_amount
            )
            ?>
        </td>
    </tr>

    <tr>
        <td></td>

        <td>
            Discount
        </td>

        <td class="right">
            -
            <?=
            $money(
                    (float)
                    $invoice->discount_amount
            )
            ?>
        </td>
    </tr>

    <tr>
        <td></td>

        <td>
            Taxable Amount
        </td>

        <td class="right">
            <?=
            $money(
                    $taxableAmount
            )
            ?>
        </td>
    </tr>

    <?php
    if (
            (float) $invoice->cgst_percent > 0
    ):
        ?>

        <tr>
            <td></td>

            <td>
                CGST
                (<?=
                number_format(
                        (float)
                        $invoice->cgst_percent,
                        2
                )
                ?>%)
            </td>

            <td class="right">
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
            (float) $invoice->sgst_percent > 0
    ):
        ?>

        <tr>
            <td></td>

            <td>
                SGST
                (<?=
                number_format(
                        (float)
                        $invoice->sgst_percent,
                        2
                )
                ?>%)
            </td>

            <td class="right">
                <?=
                $money(
                        $sgstAmount
                )
                ?>
            </td>
        </tr>

    <?php endif; ?>

    <?php
    if (
            (float) $invoice->igst_percent > 0
    ):
        ?>

        <tr>
            <td></td>

            <td>
                IGST
                (<?=
                number_format(
                        (float)
                        $invoice->igst_percent,
                        2
                )
                ?>%)
            </td>

            <td class="right">
                <?=
                $money(
                        $igstAmount
                )
                ?>
            </td>
        </tr>

    <?php endif; ?>

    <tr class="total">
        <td></td>

        <td>
            Total Payable
        </td>

        <td class="right">
            <?=
            $money(
                    (float)
                    $invoice->total_payable
            )
            ?>
        </td>
    </tr>

    <tr>
        <td></td>

        <td>
            Received
        </td>

        <td class="right">
            <?=
            $money(
                    (float)
                    $capturedAmount
            )
            ?>
        </td>
    </tr>

    <tr>
        <td></td>

        <td>
            Outstanding
        </td>

        <td class="right">
            <?=
            $money(
                    (float)
                    $outstandingAmount
            )
            ?>
        </td>
    </tr>

</table>

<!-- MANUAL PAYMENT DETAIL -->
<div class="payment-box">

    <div class="payment-title">
        Payment Details
    </div>

    <table class="payment-details">

        <?php if (
            !empty($payment['accountName'])
        ): ?>

            <tr>
                <td class="payment-label">
                    Account Name
                </td>

                <td>
                    <?= Html::encode(
                        $payment['accountName']
                    ) ?>
                </td>
            </tr>

        <?php endif; ?>

        <?php if (
            !empty($payment['bankName'])
        ): ?>

            <tr>
                <td class="payment-label">
                    Bank
                </td>

                <td>
                    <?= Html::encode(
                        $payment['bankName']
                    ) ?>
                </td>
            </tr>

        <?php endif; ?>

        <?php if (
            !empty($payment['accountNumber'])
        ): ?>

            <tr>
                <td class="payment-label">
                    Account Number
                </td>

                <td>
                    <?= Html::encode(
                        $payment['accountNumber']
                    ) ?>
                </td>
            </tr>

        <?php endif; ?>

        <?php if (
            !empty($payment['ifsc'])
        ): ?>

            <tr>
                <td class="payment-label">
                    IFSC
                </td>

                <td>
                    <?= Html::encode(
                        $payment['ifsc']
                    ) ?>
                </td>
            </tr>

        <?php endif; ?>

        <?php if (
            !empty($payment['upiId'])
        ): ?>

            <tr>
                <td class="payment-label">
                    UPI ID
                </td>

                <td>
                    <?= Html::encode(
                        $payment['upiId']
                    ) ?>
                </td>
            </tr>

        <?php endif; ?>

    </table>

    <div class="payment-note">
        Please mention invoice
        <?= Html::encode(
            $invoice->invoice_number
        ) ?>
        in the payment reference or remarks.
    </div>

</div>