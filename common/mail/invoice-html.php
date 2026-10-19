<?php
declare(strict_types=1);

use yii\helpers\Html;
?>

<h2>
    Invoice
    <?=
    Html::encode(
            $invoice->invoice_number
    )
    ?>
</h2>

<p>
    Hello
    <?=
    Html::encode(
            $invoice->client_name
    )
    ?>,
</p>

<p>
    Your invoice has been generated.
    A PDF copy is attached to this email.
</p>

<p>
    <strong>Total payable:</strong>

    <?=
            Yii::$app
            ->formatter
            ->asCurrency(
                    $invoice->total_payable,
                    'INR'
            )
    ?>
</p>

<p>
    <strong>Due date:</strong>

    <?=
            Yii::$app
            ->formatter
            ->asDate(
                    $invoice->due_date,
                    'php:d M Y'
            )
    ?>
</p>

<p>
    Please sign in to your customer portal
    to review your invoice and payment status.
</p>

<p>
    Regards,<br>
    Miracle Web Technologies
</p>