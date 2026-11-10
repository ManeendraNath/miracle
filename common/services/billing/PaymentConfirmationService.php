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

    /** @param bool $viaPayPal refunded in US dollars through PayPal (the client paid in dollars) */
    public function sendRefund(Invoice $invoice, float $amount, bool $viaGateway, bool $viaPayPal = false): void
    {
        $client = User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Customer email address is unavailable.');
        }
        $sent = Yii::$app->mailer
            ->compose(
                ['html' => '@common/mail/payment-refunded-html', 'text' => '@common/mail/payment-refunded-text'],
                [
                    'invoice' => $invoice,
                    'amount' => $amount,
                    'viaGateway' => $viaGateway,
                    'viaPayPal' => $viaPayPal,
                    // A refund without closing the invoice makes it due again, and reminders will follow.
                    'stillDue' => in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true) ? (new InvoicePaymentService())->getOutstanding($invoice) : 0.0,
                    'invoiceUrl' => $this->getInvoiceUrl($invoice),
                ],
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($client->email)
            ->setSubject('Refund issued - ' . ucfirst($invoice->customerReference()))
            ->send();
        if (!$sent) {
            throw new \RuntimeException('Refund email could not be sent.');
        }
    }

    /** Charged, but the invoice could no longer take it: the customer must hear about their money by more than a bell. */
    public function sendHeld(Invoice $invoice, float $amount): void
    {
        $client = User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Customer email address is unavailable.');
        }
        $sent = Yii::$app->mailer
            ->compose(
                ['html' => '@common/mail/payment-held-html', 'text' => '@common/mail/payment-held-text'],
                ['invoice' => $invoice, 'amount' => $amount, 'invoiceUrl' => $this->getInvoiceUrl($invoice)],
            )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($client->email)
            ->setSubject('Payment received, not yet applied - ' . ucfirst($invoice->customerReference()))
            ->send();
        if (!$sent) {
            throw new \RuntimeException('Held payment email could not be sent.');
        }
    }

    private function getInvoiceUrl(Invoice $invoice): string
    {
        $baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');

        if ($baseUrl === '') {
            throw new \RuntimeException('Frontend base URL is not configured.');
        }

        // An invoice never sent (TMP- number) has no page the customer can open yet.
        return str_starts_with((string) $invoice->invoice_number, 'TMP-') ? $baseUrl . '/payment/index' : $baseUrl . '/invoice/view?number=' . rawurlencode((string) $invoice->invoice_number);
    }
}
