<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\User;
use Yii;

final class InvoiceDeliveryService
{

    public function __construct(
            private readonly InvoicePdfService $pdfService = new InvoicePdfService()
    )
    {
        
    }

    public function send(Invoice $invoice, bool $force = false): void
    {
        if (!$force && $invoice->emailed_at !== null) {
            return;
        }
        $client = User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Invoice customer does not have a delivery email address.');
        }

        $pdf = $this->pdfService->render($invoice);

        $message = Yii::$app->mailer
                ->compose(
                        [
                            'html' => '@common/mail/invoice-html',
                            'text' => '@common/mail/invoice-text',
                        ],
                        ['invoice' => $invoice]
                )
                ->setFrom([
                    Yii::$app->params['senderEmail'] => Yii::$app->params['senderName'],
                ])
                ->setTo($client->email)
                ->setSubject('Invoice ' . $invoice->invoice_number . ' - Miracle Web Technologies')
                ->attachContent(
                        $pdf,
                        [
                            'fileName' => 'Invoice_' . $invoice->invoice_number . '.pdf',
                            'contentType' => 'application/pdf',
                        ]
                );

        if (!$message->send()) {
            throw new \RuntimeException('Invoice email could not be sent.');
        }
        $invoice->emailed_at = time();
        if (!$invoice->save(false, ['emailed_at'])) {
            throw new \RuntimeException('Invoice email delivery state could not be recorded.');
        }
    }
}
