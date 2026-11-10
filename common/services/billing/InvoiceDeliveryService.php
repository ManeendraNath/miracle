<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\User;
use common\services\mail\AdminMailHelper;
use Yii;

final class InvoiceDeliveryService
{
    public function __construct(
        private readonly InvoicePdfService $pdfService = new InvoicePdfService()
    ) {
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
        // Claimed before sending: staff clicking Send while the nightly run mails the same invoice send it once.
        // ponytail: a crash mid-send leaves it marked sent; staff can resend from the invoice page.
        $claimed = time();
        if (!$force && Invoice::updateAll(['emailed_at' => $claimed], ['id' => (int) $invoice->id, 'emailed_at' => null]) === 0) {
            return;
        }
        try {
            $this->deliver($invoice, $client);
        } catch (\Throwable $e) {
            if (!$force) {
                // Only this claim: a resend that succeeded meanwhile keeps its time.
                Invoice::updateAll(['emailed_at' => null], ['id' => (int) $invoice->id, 'emailed_at' => $claimed]);
            }
            throw $e;
        }
        if ($force) {
            Invoice::updateAll(['emailed_at' => $claimed], ['id' => (int) $invoice->id]);
        }
        $invoice->emailed_at = $claimed;
    }

    private function deliver(Invoice $invoice, User $client): void
    {

        try {
            \common\services\payment\PayPalService::lockRate($invoice); // the dollar price quoted in this email holds until it is due
        } catch (\Throwable $e) {
            Yii::warning(['message' => 'Could not lock the PayPal rate.', 'invoiceId' => (int) $invoice->id, 'error' => $e->getMessage()], __METHOD__);
        }
        $pdf = $this->pdfService->render($invoice);

        $message = Yii::$app->mailer
                ->compose(
                    [
                            'html' => '@common/mail/invoice-html',
                            'text' => '@common/mail/invoice-text',
                        ],
                    ['invoice' => $invoice, 'outstanding' => (new InvoicePaymentService())->getOutstanding($invoice)]
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
        AdminMailHelper::addBillingBcc($message);
        if (!$message->send()) {
            throw new \RuntimeException('Invoice email could not be sent.');
        }
    }
}
