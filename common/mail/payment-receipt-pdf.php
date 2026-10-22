<?php
declare(strict_types=1);

use yii\helpers\Html;

$money = static fn(int|float|string|null $amount): string => 'INR ' . number_format((float) ($amount ?? 0), 2);
?>

<style>

    body {
        font-family: sans-serif;
        color: #172033;
        font-size: 11pt;
    }

    .header {
        border-bottom: 2px solid #0f172a;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }

    .title {
        font-size: 23pt;
        font-weight: bold;
    }

    .success {
        color: #15803d;
        font-weight: bold;
    }

    table.details {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .details td {
        border-bottom: 1px solid #e5e7eb;
        padding: 9px;
    }

    .details td:first-child {
        width: 38%;
        color: #64748b;
    }

    .amount {
        margin-top: 30px;
        padding: 18px;
        background: #f0fdf4;
        border: 1px solid #86efac;
        font-size: 18pt;
        font-weight: bold;
    }

    .footer {
        font-size: 9pt;
        color: #64748b;
        margin-top: 35px;
    }

</style>

<div class="header">
    <div class="title">Miracle Web Technologies</div>
    <div>PAYMENT RECEIPT</div>
</div>

<div class="success">Payment Confirmed</div>
<div class="amount"><?= Html::encode($money($transaction->amount)) ?></div>
<table class="details">
    <tr>
        <td>Invoice</td>
        <td><?= Html::encode($invoice->invoice_number) ?></td>
    </tr>
    <tr>
        <td>Customer</td>
        <td><?= Html::encode($invoice->client_name) ?></td>
    </tr>
    <tr>
        <td>Payment Reference</td>
        <td><?= Html::encode($transaction->gateway_payment_id ?: '-') ?></td>
    </tr>
    <tr>
        <td>Confirmed Date</td>
        <td><?= $transaction->paid_at ? Yii::$app->formatter->asDatetime($transaction->paid_at, 'php:d M Y, h:i A') : '-' ?>
        </td>
    </tr>
    <tr>
        <td>Remaining Invoice Balance</td>
        <td><?= Html::encode($money($outstandingAmount)) ?></td>
    </tr>
</table>

<div class="footer">
    This receipt confirms that the payment above has been recorded by Miracle Web Technologies.
    <br><br>
    This is a system-generated payment receipt.
</div>
