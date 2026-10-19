<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Yii;

final class InvoicePdfService
{

    public function render(
            Invoice $invoice
    ): string
    {
        if ($invoice->getIsNewRecord()) {
            throw new \LogicException(
                            'Invoice must be persisted before PDF generation.'
                    );
        }

        $paymentService = new InvoicePaymentService();

        $html = Yii::$app->view
                ->renderFile(
                        Yii::getAlias(
                                '@common/mail/invoice-pdf.php'
                        ),
                        [
                            'invoice' => $invoice,
                            'capturedAmount' =>
                            $paymentService
                            ->getCapturedAmount(
                                    (int) $invoice->id
                            ),
                            'outstandingAmount' =>
                            $paymentService
                            ->getOutstanding(
                                    $invoice
                            ),
                        ]
                );

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 12,
            'margin_right' => 12,
            'margin_bottom' => 12,
            'margin_left' => 12,
        ]);

        $mpdf->WriteHTML(
                $html
        );

        return $mpdf->Output(
                        '',
                        Destination::STRING_RETURN
                );
    }
}
