<?php
declare(strict_types=1);

use yii\helpers\Html;

$frontendBaseUrl = rtrim(
        (string)
        (
        Yii::$app
        ->params['frontendBaseUrl'] ?? ''
        ),
        '/'
);

$invoiceUrl = $frontendBaseUrl !== '' ? $frontendBaseUrl
        . '/invoice/view?number='
        . rawurlencode(
                $invoice->invoice_number
        ) : '#';

$total = Yii::$app->formatter
        ->asCurrency(
                $invoice->total_payable,
                'INR'
        );

$due = Yii::$app->formatter
        ->asDate(
                $invoice->due_date,
                'php:d M Y'
        );
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

        <table
            width="100%"
            cellpadding="0"
            cellspacing="0"
            role="presentation"
            >
            <tr>
                <td style="
                    padding:0 4px 22px;
                    font-size:25px;
                    font-weight:800;
                    ">
                    Miracle Web Technologies
                </td>

                <td
                    align="right"
                    style="
                    padding-bottom:22px;
                    color:#6b7280;
                    font-size:13px;
                    "
                    >
                    Invoice
<?=
Html::encode(
        $invoice->invoice_number
)
?>
                </td>
            </tr>
        </table>

        <div style="
             background:#ffffff;
             border:1px solid #2563eb;
             border-radius:18px;
             padding:38px 40px;
             ">

            <div style="
                 color:#2563eb;
                 font-size:13px;
                 font-weight:700;
                 text-transform:uppercase;
                 letter-spacing:0.08em;
                 margin-bottom:14px;
                 ">
                New Invoice
            </div>

            <h1 style="
                margin:0 0 14px;
                font-size:31px;
                line-height:1.25;
                ">
                Your invoice is ready.
            </h1>

            <p style="
               margin:0 0 26px;
               color:#4b5563;
               font-size:16px;
               line-height:1.7;
               ">
                Hello <?= Html::encode($invoice->client_name) ?>, your latest billing invoice has been generated.
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
                        Total payable
                    </td>

                    <td
                        align="right"
                        style="
                        padding:18px;
                        font-size:20px;
                        font-weight:700;
                        "
                        >
<?=
Html::encode(
        $total
)
?>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:0 18px 18px;
                        ">
                        Pay by
                    </td>

                    <td
                        align="right"
                        style="
                        padding:0 18px 18px;
                        color:#dc2626;
                        font-weight:700;
                        "
                        >
<?=
Html::encode(
        $due
)
?>
                    </td>
                </tr>
            </table>

            <?=
            Html::a(
                    'View Invoice & Pay →',
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
             background:#ffffff;
             border-radius:18px;
             padding:26px 40px;
             margin-top:20px;
             color:#4b5563;
             line-height:1.7;
             font-size:14px;
             ">

            <strong style="color:#171717">Payment options</strong>
            <br>
            You can pay using the bank / UPI details shown on the attached invoice and then upload the payment screenshot from your customer portal.
            <br><br>
            Please mention <strong><?= Html::encode($invoice->invoice_number) ?></strong> in the payment reference.
        </div>

        <div style="
             text-align:center;
             padding:24px 12px;
             color:#9ca3af;
             font-size:12px;
             line-height:1.6;
             ">
            Miracle Web Technologies<br>
            Automated billing notification
        </div>

    </div>

</div>