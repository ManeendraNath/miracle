<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var float $amount */
/** @var bool $viaGateway */
/** @var string $invoiceUrl */
echo $this->render('_card', [
    'tone' => 'info',
    'title' => 'We have refunded your payment',
    'intro' => $viaGateway ? 'It usually reaches your account within 5 to 7 working days.' : 'It was sent back to you directly.',
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Refunded', Yii::$app->formatter->asCurrency($amount, 'INR')],
    ],
    'button' => ['View Invoice', $invoiceUrl],
]);
