<?php
declare(strict_types=1);

use yii\helpers\Html;

/**
 * @var common\models\Invoice $invoice
 * @var common\models\User $client
 * @var string $clientPortalUrl
 */
$this->title = 'Payment Due Reminder';

$amount = Yii::$app->formatter->asCurrency(
        (float) $invoice->total_payable,
        'INR'
);

$dueDate = Yii::$app->formatter->asDate(
        $invoice->due_date,
        'php:d M Y'
);
?>

<style>
    @media only screen and (max-width: 600px) {

        .invoice-summary td {
            display:block !important;
            width:100% !important;
            box-sizing:border-box;
        }

        .action-button {
            display:block !important;
            width:100% !important;
            box-sizing:border-box;
            text-align:center !important;
        }

        .text-center-mobile {
            text-align:center !important;
        }
    }
</style>

<div
    role="article"
    aria-label="Invoice Due Reminder"
    >

    <div
        style="
        background:#fff4e5;
        border-left:5px solid #ff9800;
        padding:15px;
        margin-bottom:25px;
        "
        >
        <strong>
            Payment Due Reminder
        </strong>
    </div>

    <h2
        style="
        margin-top:0;
        color:#222;
        "
        >
        Hello <?=
Html::encode(
        $invoice->client_name
)
?>,
    </h2>

    <p
        style="
        color:#555;
        line-height:1.8;
        "
        >
        This is a friendly reminder that the following invoice is due tomorrow.
    </p>

    <table
        width="100%"
        cellpadding="10"
        cellspacing="0"
        class="invoice-summary"
        role="presentation"
        style="
        border:1px solid #e5e5e5;
        border-collapse:collapse;
        margin:25px 0;
        "
        >

        <tr>
            <td width="40%">
                Invoice Number
            </td>

            <td>
                <strong>
                    <?=
                    Html::encode(
                            $invoice->invoice_number
                    )
                    ?>
                </strong>
            </td>
        </tr>

        <tr>
            <td>
                Due Date
            </td>

            <td>
                <strong>
<?= Html::encode($dueDate) ?>
                </strong>
            </td>
        </tr>

        <tr>
            <td>
                Amount Due
            </td>

            <td
                style="
                color:#d32f2f;
                font-size:18px;
                font-weight:bold;
                "
                >
<?= $amount ?>
            </td>
        </tr>

    </table>

    <p
        style="
        color:#555;
        line-height:1.8;
        "
        >
        To avoid interruption of services, please complete payment before the due date.
    </p>

    <div
        class="text-center-mobile"
        style="
        text-align:center;
        margin:35px 0;
        "
        >

        <a
            href="<?=
Html::encode(
        $clientPortalUrl
)
?>"
            class="action-button"
            aria-label="View Invoice"
            style="
            background:#1565c0;
            color:#ffffff;
            text-decoration:none;
            padding:14px 28px;
            border-radius:6px;
            font-weight:bold;
            display:inline-block;
            "
            >
            View Invoice
        </a>

    </div>

    <p
        style="
        color:#666;
        line-height:1.8;
        "
        >
        If payment has already been submitted, please ignore this reminder.
    </p>

</div>