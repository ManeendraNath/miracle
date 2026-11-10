<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var bool $viaGateway */
/** @var bool $viaPayPal */
/** @var float $stillDue */
/** @var string $invoiceUrl */
echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'We have refunded your payment',
    'intro' => $viaGateway ? 'It usually reaches your account within 5 to 7 working days.' : 'It was sent back to you directly.',
    'rows' => [
        ['Invoice', ucfirst($invoice->customerReference())],
        ['Refunded', Yii::$app->formatter->asCurrency($amount, 'INR') . (!empty($viaPayPal) ? ' (returned to your PayPal account in US dollars)' : '')],
        ...($stillDue > 0 ? [['Balance still due on this invoice', Yii::$app->formatter->asCurrency($stillDue, 'INR'), 'accent']] : []),
    ],
    'button' => [str_starts_with((string) $invoice->invoice_number, 'TMP-') ? 'View Payments' : 'View Invoice', $invoiceUrl],
]);
