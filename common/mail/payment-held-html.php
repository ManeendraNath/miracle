<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var string $invoiceUrl */
echo $this->render('_card', [
    'tone' => 'warning',
    'title' => 'We received your payment',
    'intro' => 'This invoice can no longer take payments, so the money has not been applied to it. Our team will refund it or apply it to another of your invoices.',
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Received', Yii::$app->formatter->asCurrency($amount, 'INR')],
    ],
    'button' => ['View Invoice', $invoiceUrl],
]);
