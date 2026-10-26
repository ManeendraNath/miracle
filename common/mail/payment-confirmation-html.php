<?php
declare(strict_types=1);

use yii\helpers\Html;

$paidAmount = Yii::$app->formatter
        ->asCurrency(
                $transaction->amount,
                'INR'
        );

$outstanding = Yii::$app->formatter
        ->asCurrency(
                $outstandingAmount,
                'INR'
        );

$isPaid = $invoice->status ===
        \common\models\Invoice::STATUS_PAID;
?>

<div style="
     margin:0;
     padding:32px 12px;
     background:#f3f4f6;
     font-family:Arial,Helvetica,sans-serif;
     color:#171717;
     ">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <div style="
         max-width:680px;
         margin:0 auto;
         ">

        <div style="
             font-size:25px;
             font-weight:800;
             margin:0 4px 22px;
             ">
            Miracle Web Technologies
        </div>

        <div style="
             background:#ffffff;
             border:1px solid #22c55e;
             border-radius:18px;
             padding:38px 40px;
             ">

            <div style="
                 font-size:44px;
                 line-height:1;
                 color:#16a34a;
                 margin-bottom:20px;
                 ">
                ✓
            </div>

            <h1 style="
                margin:0 0 14px;
                font-size:31px;
                line-height:1.25;
                ">
                Payment confirmed.
            </h1>

            <p style="
               margin:0 0 26px;
               font-size:16px;
               line-height:1.7;
               color:#4b5563;
               ">
                We have verified and recorded your payment.
                Thank you for your payment.
            </p>

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                style="
                background:#f9fafb;
                border-radius:12px;
                margin-bottom:28px;
                "
                role="presentation"
                >

                <tr>
                    <td style="padding:18px">
                        Invoice
                    </td>

                    <td
                        align="right"
                        style="
                        padding:18px;
                        font-weight:700;
                        "
                        >
<?=
Html::encode(
        $invoice
        ->invoice_number
)
?>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:0 18px 18px;
                        ">
                        Amount received
                    </td>

                    <td
                        align="right"
                        style="
                        padding:0 18px 18px;
                        color:#16a34a;
                        font-weight:700;
                        "
                        >
<?=
Html::encode(
        $paidAmount
)
?>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:0 18px 18px;
                        ">
                        Payment reference
                    </td>

                    <td
                        align="right"
                        style="
                        padding:0 18px 18px;
                        font-weight:600;
                        "
                        >
                            <?=
                            Html::encode(
                                    $transaction
                                    ->gateway_payment_id ?: 'Recorded payment'
                            )
                            ?>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:0 18px 18px;
                        ">
                        Remaining balance
                    </td>

                    <td
                        align="right"
                        style="
                        padding:0 18px 18px;
                        font-weight:700;
                        "
                        >
                            <?=
                            Html::encode(
                                    $outstanding
                            )
                            ?>
                    </td>
                </tr>

            </table>

<?php if ($isPaid): ?>

                <div style="
                     background:#ecfdf5;
                     border-left:4px solid #22c55e;
                     padding:14px 16px;
                     margin-bottom:26px;
                     color:#166534;
                     line-height:1.6;
                     ">
                    This invoice is fully paid.
                    Any linked renewal services have been
                    processed by the billing system.
                </div>

<?php else: ?>

                <div style="
                     background:#fff7ed;
                     border-left:4px solid #f97316;
                     padding:14px 16px;
                     margin-bottom:26px;
                     color:#9a3412;
                     line-height:1.6;
                     ">
                    This payment has been applied successfully.
                    A remaining balance is still due on the invoice.
                </div>

            <?php endif; ?>

            <?=
            Html::a(
                    'View Invoice →',
                    $invoiceUrl,
                    [
                        'style' =>
                        'display:inline-block;'
                        . 'background:#171717;'
                        . 'color:#ffffff;'
                        . 'text-decoration:none;'
                        . 'font-weight:700;'
                        . 'padding:16px 25px;'
                        . 'border-radius:7px;',
                    ]
            )
            ?>

        </div>

        <div style="
             padding:24px 12px;
             text-align:center;
             font-size:12px;
             color:#9ca3af;
             line-height:1.6;
             ">
            Miracle Web Technologies<br>
            Automated payment confirmation
        </div>

    </div>

</div>