<?php

declare(strict_types=1);

namespace common\services\billing;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Yii;
use common\models\Invoice;
use common\models\Transaction;

final class PaymentReceiptPdfService
{
    public function render(Transaction $transaction): string
    {
        if ($transaction->status !== Transaction::STATUS_CAPTURED) {
            throw new \DomainException('A receipt is available only for confirmed payments.');
        }
        $invoice = Invoice::findOne((int) $transaction->invoice_id);
        if ($invoice === null) {
            throw new \RuntimeException('Invoice does not exist for this payment.');
        }
        $paymentService = new InvoicePaymentService();
        $html = Yii::$app->view->renderFile(
            Yii::getAlias('@common/mail/payment-receipt-pdf.php'),
            [
                    'transaction' => $transaction,
                    'invoice' => $invoice,
                    'outstandingAmount' => $paymentService->getOutstanding($invoice),
                    'carriedInto' => $invoice->status === Invoice::STATUS_CARRIED_FORWARD ? $paymentService->openInvoiceCarrying((int) $invoice->id) : null,
                ]
        );

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 14,
            'margin_right' => 14,
            'margin_bottom' => 14,
            'margin_left' => 14,
            // The default is inside vendor/, which deploys may leave unwritable.
            'tempDir' => Yii::getAlias('@runtime/mpdf'),
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
