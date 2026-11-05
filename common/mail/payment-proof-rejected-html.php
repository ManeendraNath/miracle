<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var string $reference */
/** @var string $invoiceUrl */
echo $this->render('_card', [
    'tone' => 'warning',
    'title' => 'We could not verify your payment proof',
    'intro' => 'Please check the payment reference and submit it again, or contact support if the amount was deducted.',
    'rows' => array_filter([
        ['Invoice', $invoice->invoice_number],
        $reference !== '' ? ['Reference you submitted', $reference] : null,
    ]),
    'button' => ['View Invoice', $invoiceUrl],
]);
