<?php
declare(strict_types=1);

use yii\helpers\Html;

$isOverdue = $daysUntilDue < 0;
$isDueToday = $daysUntilDue === 0;

if ($isOverdue) {
    $headline = 'Your payment is overdue.';
    $subHeadline = 'Please complete the payment to keep your services up to date.';
} elseif ($isDueToday) {
    $headline = 'Your payment is due today.';
    $subHeadline = 'Please complete the payment for this invoice today.';
} else {
    $headline = sprintf('You have %d day%s left to pay.', $daysUntilDue, $daysUntilDue === 1 ? '' : 's');
    $subHeadline = 'Your invoice is still awaiting payment.';
}
$dueDate = Yii::$app->formatter->asDate($invoice->due_date, 'php:d M Y');
$amount = Yii::$app->formatter->asCurrency($outstandingAmount, 'INR');
?>

<div style="margin:0; padding:32px 12px; background:#f3f4f6; font-family:Arial,Helvetica,sans-serif; color:#171717;">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <div style="max-width:680px; margin:0 auto;">
        <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
                <td style="padding:0 4px 22px; font-size:25px;font-weight:800; color:#111827;">Miracle Web Technologies</td>

                <td align="right"
                    style="
                    padding-bottom:22px;
                    font-size:13px;
                    color:#6b7280;
                    ">
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
             border:1px solid #ef4444;
             border-radius:18px;
             padding:38px 40px;
             ">

            <h1 style="
                margin:0 0 14px;
                font-size:31px;
                line-height:1.25;
                color:#171717;
                ">
                    <?=
                    Html::encode(
                            $headline
                    )
                    ?>
            </h1>

            <p style="
               margin:0 0 28px;
               font-size:16px;
               line-height:1.7;
               color:#4b5563;
               ">
                   <?=
                   Html::encode(
                           $subHeadline
                   )
                   ?>
            </p>

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                style="
                margin-bottom:28px;
                background:#f9fafb;
                border-radius:12px;
                "
                role="presentation"
                >
                <tr>
                    <td style="
                        padding:18px;
                        font-size:14px;
                        color:#6b7280;
                        ">
                        Outstanding
                    </td>

                    <td
                        align="right"
                        style="
                        padding:18px;
                        font-size:19px;
                        font-weight:700;
                        "
                        >
                            <?=
                            Html::encode(
                                    $amount
                            )
                            ?>
                    </td>
                </tr>

                <tr>
                    <td style="
                        padding:0 18px 18px;
                        color:#6b7280;
                        ">
                        Due date
                    </td>

                    <td
                        align="right"
                        style="
                        padding:0 18px 18px;
                        font-weight:700;
                        color:#dc2626;
                        "
                        >
                            <?= Html::encode($dueDate) ?>
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
                        . 'font-size:16px;'
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
             padding:28px 40px;
             margin-top:20px;
             ">

            <div style="
                 font-size:14px;
                 color:#374151;
                 line-height:1.7;
                 ">
                <strong>Invoice <?= Html::encode($invoice->invoice_number) ?></strong>
                <br>
                Billed to: <?= Html::encode($invoice->client_name) ?>
                <br>
                Please use the invoice number as the payment reference when paying by bank transfer or UPI.
            </div>

        </div>

        <div style="padding:24px 12px; text-align:center; color:#9ca3af; font-size:12px;line-height:1.6;">
            Miracle Web Technologies<br>
            This is an automated billing reminder.
        </div>

    </div>

</div>