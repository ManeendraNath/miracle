<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;
use common\models\Transaction;
use common\models\User;
use Yii;

final class PaymentConfirmationService
{
    public function send(Transaction $transaction): void
    {
        if ($transaction->status !== Transaction::STATUS_CAPTURED) {
            return;
        }

        if (!empty($transaction->confirmation_emailed_at)) {
            return;
        }

        $invoice = Invoice::findOne((int) $transaction->invoice_id);

        if ($invoice === null) {
            throw new \RuntimeException('Invoice not found for ' . 'payment confirmation.');
        }

        $client = User::findOne((int) $invoice->user_id);

        if ($client === null || empty($client->email)) {
            throw new \DomainException('Customer email address ' . 'is unavailable.');
        }

        $paymentService = new InvoicePaymentService();

        $outstanding = $paymentService->getOutstanding($invoice);

        $invoiceUrl = $this->getInvoiceUrl($invoice);

        $message = Yii::$app->mailer
            ->compose(
                [
                    'html' => '@common/mail/payment-confirmation-html',
                    'text' => '@common/mail/payment-confirmation-text',
                ],
                [
                    'invoice' => $invoice,
                    'transaction' => $transaction,
                    'outstandingAmount' => $outstanding,
                    'invoiceUrl' => $invoiceUrl,
                ],
            )
            ->setFrom([
                Yii::$app->params['senderEmail'] => Yii::$app->params['senderName'],
            ])
            ->setTo($client->email)
            ->setSubject('Payment received - ' . $invoice->invoice_number);

        if (!$message->send()) {
            throw new \RuntimeException('Payment confirmation email ' . 'could not be sent.');
        }

        $transaction->confirmation_emailed_at = time();

        if (!$transaction->save(false, ['confirmation_emailed_at'])) {
            throw new \RuntimeException('Payment confirmation delivery ' . 'could not be recorded.');
        }
    }

    /** Tells the customer their UPI/bank proof was rejected. $reference: as they submitted it. */
    public function sendProofRejected(Transaction $transaction, string $reference): void
    {
        $invoice = Invoice::findOne((int) $transaction->invoice_id);
        $client = $invoice === null ? null : User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Customer email address is unavailable.');
        }
        $sent = Yii::$app->mailer
            ->compose(
                ['html' => '@common/mail/payment-proof-rejected-html', 'text' => '@common/mail/payment-proof-rejected-text'],
                ['invoice' => $invoice, 'reference' => $reference, 'invoiceUrl' => $this->getInvoiceUrl($invoice)],
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($client->email)
            ->setSubject('Payment proof not accepted - ' . $invoice->invoice_number)
            ->send();
        if (!$sent) {
            throw new \RuntimeException('Rejected proof email could not be sent.');
        }
    }

    public function sendRefund(Invoice $invoice, float $amount, bool $viaGateway): void
    {
        $client = User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Customer email address is unavailable.');
        }
        $sent = Yii::$app->mailer
            ->compose(
                ['html' => '@common/mail/payment-refunded-html', 'text' => '@common/mail/payment-refunded-text'],
                ['invoice' => $invoice, 'amount' => $amount, 'viaGateway' => $viaGateway, 'invoiceUrl' => $this->getInvoiceUrl($invoice)],
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($client->email)
            ->setSubject('Refund issued - ' . $invoice->invoice_number)
            ->send();
        if (!$sent) {
            throw new \RuntimeException('Refund email could not be sent.');
        }
    }

    private function getInvoiceUrl(Invoice $invoice): string
    {
        $baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('Frontend base URL is not configured.');
        }

        return $baseUrl . '/invoice/view?number=' . rawurlencode((string) $invoice->invoice_number);
    }
}
