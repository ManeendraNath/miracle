<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $outstandingAmount */
/** @var int $daysUntilDue */
/** @var string $invoiceUrl */
if ($daysUntilDue < 0) {
    [$tone, $title, $intro] = ['danger', 'Your payment is overdue.', 'Please complete the payment to keep your services up to date.'];
} elseif ($daysUntilDue === 0) {
    [$tone, $title, $intro] = ['warning', 'Your payment is due today.', 'Please complete the payment for this invoice today.'];
} else {
    $title = sprintf('You have %d day%s left to pay.', $daysUntilDue, $daysUntilDue === 1 ? '' : 's');
    [$tone, $intro] = ['info', 'Your invoice is still awaiting payment.'];
}

echo $this->render('_card', [
    'tone' => $tone,
    'eyebrow' => 'Payment reminder',
    'title' => $title,
    'intro' => $intro,
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Billed to', $invoice->client_name],
        ['Outstanding', Yii::$app->formatter->asCurrency($outstandingAmount, 'INR')],
        ['Due date', Yii::$app->formatter->asDate($invoice->due_date, 'php:d M Y'), 'accent'],
        ...(($usd = \common\services\payment\PayPalService::lockedPrice($invoice, (float) $outstandingAmount)) !== null ? [['Or pay with PayPal', $usd]] : []),
    ],
    'button' => ['View Invoice & Pay', $invoiceUrl],
    'footnote' => 'Paying by bank transfer or UPI? Use ' . $invoice->invoice_number . ' as the payment reference.',
]);
