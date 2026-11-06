<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $outstanding */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');

echo $this->render('_card', [
    'tone' => 'info',
    'eyebrow' => 'New invoice',
    'title' => 'Your invoice is ready.',
    'intro' => 'Hello ' . $invoice->client_name . ', your latest invoice has been generated. The PDF is attached.',
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Total payable', Yii::$app->formatter->asCurrency($invoice->total_payable, 'INR')],
        ...($outstanding < (float) $invoice->total_payable ? [['Outstanding after payments received', Yii::$app->formatter->asCurrency($outstanding, 'INR')]] : []),
        ['Pay by', Yii::$app->formatter->asDate($invoice->due_date, 'php:d M Y'), 'accent'],
    ],
    'button' => $baseUrl !== '' ? ['View Invoice & Pay', $baseUrl . '/invoice/view?number=' . rawurlencode($invoice->invoice_number)] : null,
    'footnote' => 'You can pay online from the customer portal, or by bank transfer / UPI using the details on the invoice and then upload the payment screenshot. '
        . 'Please mention ' . $invoice->invoice_number . ' in the payment reference.',
]);
