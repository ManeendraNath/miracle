<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var common\models\Invoice $invoice */
/** @var common\models\Transaction $transaction */
/** @var float $outstandingAmount */
/** @var string $invoiceUrl */
$formatter = Yii::$app->formatter;
$isPaid = $invoice->status === common\models\Invoice::STATUS_PAID;

echo $this->render('_card', [
    'tone' => 'success',
    'icon' => '✓',
    'title' => 'Payment confirmed.',
    'intro' => 'We have verified and recorded your payment. Thank you for your payment.',
    'rows' => [
        ['Invoice', $invoice->invoice_number],
        ['Amount received', $formatter->asCurrency((float) $transaction->amount - (float) $transaction->refunded_amount, 'INR'), 'accent'],
        ...($transaction->paidInUsd() !== null ? [['Paid with PayPal', $transaction->paidInUsd()]] : []),
        ['Payment reference', $transaction->gateway_payment_id ?: 'Recorded payment'],
        ['Remaining balance', $formatter->asCurrency($outstandingAmount, 'INR')],
    ],
    'note' => $isPaid
        ? ['This invoice is fully paid. Any linked renewal services have been processed by the billing system.']
        : ['This payment has been applied. A remaining balance is still due on the invoice.', 'warning'],
    'button' => ['View Invoice', $invoiceUrl],
]);
