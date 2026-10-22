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
                ]
        );

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 14,
            'margin_right' => 14,
            'margin_bottom' => 14,
            'margin_left' => 14,
        ]);

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
