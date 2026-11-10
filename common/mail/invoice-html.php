<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $outstanding */
$baseUrl = (string) (Yii::$app->params['frontendBaseUrl'] ?? '');
$dueOn = Yii::$app->formatter->asDate($invoice->due_date, 'php:d M Y');
$daysExpired = $invoice->renewalDaysExpired();
[$tone, $title, $intro, $dueLabel] = match (true) {
    $daysExpired === null => ['info', 'Your invoice is ready.', 'your latest invoice has been generated.', 'Pay by'],
    $daysExpired > 0 => ['danger', 'Your service has expired.', 'your service expired on ' . $dueOn . ', ' . $daysExpired . ' day' . ($daysExpired === 1 ? '' : 's')
        . ' ago. Please pay this renewal invoice now to renew it.', 'Expired on'],
    $daysExpired === 0 => ['warning', 'Your service expires today.', 'your service expires today. Please pay this renewal invoice today to keep it running.', 'Pay by (expiry date)'],
    default => ['info', 'Your renewal invoice is ready.', 'your renewal invoice has been generated. Please pay by the expiry date to keep your service running.', 'Pay by (expiry date)'],
};

echo $this->render('_card', [
    'tone' => $tone,
    'eyebrow' => 'New invoice',
    'title' => $title,
    'intro' => 'Hello ' . $invoice->client_name . ', ' . $intro . ' The PDF is attached.',
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Total payable', Yii::$app->formatter->asCurrency($invoice->total_payable, 'INR')],
        ...($outstanding < (float) $invoice->total_payable ? [['Outstanding after payments received', Yii::$app->formatter->asCurrency($outstanding, 'INR')]] : []),
        [$dueLabel, $dueOn, 'accent'],
        ...(($usd = \common\services\payment\PayPalService::lockedPrice($invoice, $outstanding)) !== null ? [['Or pay with PayPal', $usd]] : []),
    ],
    'button' => $baseUrl !== '' ? ['View Invoice & Pay', $baseUrl . '/invoice/view?number=' . rawurlencode($invoice->invoice_number)] : null,
    'footnote' => 'You can pay online from the customer portal, or by bank transfer / UPI using the details on the invoice and then upload the payment screenshot. '
        . 'Please mention ' . $invoice->invoice_number . ' in the payment reference.',
]);
