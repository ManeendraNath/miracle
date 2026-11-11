<?php

declare(strict_types=1);

namespace common\services\mail;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\services\billing\InvoicePaymentService;
use common\models\User;
use Yii;

/** One-off emails staff write to a customer (backend user/email). */
final class CustomerEmailService
{
    /**
     * Ready-made emails to start from, first one preselected. [EDIT: ...] marks what staff must write themselves;
     * the form refuses to send while one is left. Without a client (an email to several) the greeting keeps {name},
     * filled in for each recipient by send().
     *
     * @return array<string, array{label: string, subject: string, message: string}>
     */
    public function templates(?User $client, ?Invoice $invoice = null): array
    {
        $list = [];
        if ($invoice !== null) {
            $number = $invoice->invoice_number;
            $sent = Yii::$app->formatter->asDate((int) $invoice->created_at, 'php:j F Y');
            if ($invoice->status === Invoice::STATUS_CANCELLED) {
                $list['invoice-cancelled'] = ['Invoice cancelled (corrected one to follow)', "Invoice {$number} cancelled – please do not pay it",
                    "Invoice {$number}, which we sent on {$sent}, [EDIT: what was wrong, e.g. showed the hosting renewal period as two years instead of one]. "
                    . "We have cancelled it, so please do not pay it.\n\n"
                    . "You will receive the corrected invoice by email shortly. You can also view and pay it when you sign in to your account on our website.\n\n"
                    . 'We apologise for the confusion.'];
            }
            if (in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID], true)) {
                $due = !$invoice->due_date ? ' is due'
                    : ((string) $invoice->due_date < date('Y-m-d') ? ' was due on ' : ' is due on ') . Yii::$app->formatter->asDate((string) $invoice->due_date, 'php:j F Y');
                $outstanding = (new InvoicePaymentService())->getOutstanding($invoice);
                $owed = Yii::$app->formatter->asCurrency($outstanding, 'INR');
                // The same dollar price as the invoice email and the automatic reminders, for a client paying by PayPal.
                $usd = \common\services\payment\PayPalService::lockedPrice($invoice, $outstanding);
                $list['invoice-reminder'] = ['Payment reminder', "Reminder: invoice {$number}",
                    "This is a friendly reminder that {$owed} on invoice {$number}{$due}. "
                    . ($usd !== null ? "With PayPal it is {$usd}. " : '')
                    . "You can view and pay it when you sign in to your account on our website.\n\n"
                    . 'If you have already paid, please ignore this email, and thank you.'];
            }
            if ($invoice->status === Invoice::STATUS_PAID) {
                $list['invoice-thanks'] = ['Payment received – thank you', "Thank you for paying invoice {$number}",
                    "We have received your payment for invoice {$number}. Thank you for your prompt payment."];
            }
            $list['invoice-other'] = ['Other message about this invoice', "About invoice {$number}", "[EDIT: your message about invoice {$number}, sent on {$sent}.]"];
        }
        $list += [
            'own' => ['Write your own', '', '[EDIT: your message]'],
            'maintenance' => ['Scheduled maintenance', 'Scheduled maintenance on [EDIT: date]',
                'We will carry out scheduled maintenance on your [EDIT: website / hosting / email] on [EDIT: date and time]. '
                . "During this time [EDIT: what you may notice, e.g. the website may be unavailable for a short while].\n\n"
                . 'No action is needed from you.'],
            'renewal' => ['Renewal coming up', 'Your [EDIT: service] renewal',
                'Your [EDIT: hosting / domain / maintenance] for [EDIT: domain name] expires on [EDIT: date]. '
                . "We will send you the renewal invoice before then.\n\n"
                . 'If you do not wish to renew, please let us know.'],
            'information' => ['Information needed from you', 'Information needed for [EDIT: your website]',
                "To continue with [EDIT: the work], we need [EDIT: content, logo, access details or your approval].\n\n"
                . 'Please reply to this email with it.'],
            'project-update' => ['Website project update', 'Update on [EDIT: your website project]',
                "Here is an update on your website project:\n\n[EDIT: what has been done]\n\nNext steps: [EDIT: what happens next]."],
        ];
        $name = $invoice?->client_name ?: ($client !== null ? $this->nameOf($client) : '{name}');
        $signOff = "\n\nIf you have any questions, just reply to this email.\n\nWarm regards,\n" . Yii::$app->params['senderName'];

        return array_map(static fn (array $t): array => ['label' => $t[0], 'subject' => $t[1], 'message' => 'Dear ' . $name . ",\n\n" . $t[2] . $signOff], $list);
    }

    public function nameOf(User $client): string
    {
        return (string) (CustomerBillingProfile::find()->select('legal_name')->where(['user_id' => (int) $client->id])->scalar() ?: $client->username);
    }

    /** $copyToBilling false for an email to many: the caller sends billing one copy instead of one per customer. */
    public function send(User $client, string $subject, string $body, bool $aboutInvoice = false, bool $copyToBilling = true): void
    {
        if (empty($client->email)) {
            throw new \DomainException('This customer has no email address.');
        }
        $body = str_replace('{name}', $this->nameOf($client), $body);
        $base = (string) Yii::$app->params['frontendBaseUrl'];
        $message = Yii::$app->mailer->compose(
            ['html' => '@common/mail/customer-message-html', 'text' => '@common/mail/customer-message-text'],
            ['subject' => $subject, 'body' => $body, 'button' => $aboutInvoice ? ['View Your Invoices', $base . '/invoice/index'] : ['Go to Your Account', $base . '/site/login']],
        )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($client->email)
            ->setSubject($subject);
        // A record of what was written: the billing address, or the admin address when there is none (as sendCopy).
        $copy = (string) (Yii::$app->params['adminBillingEmail'] ?? '') ?: (string) (Yii::$app->params['adminEmail'] ?? '');
        if ($copyToBilling && $copy !== '') {
            $message->setBcc($copy);
        }
        if (!$message->send()) {
            throw new \RuntimeException('The email could not be sent.');
        }
    }

    /** One copy of an email sent to many, to the billing address, saying who got it. */
    public function sendCopy(string $subject, string $body, array $sentTo): void
    {
        $billing = (string) (Yii::$app->params['adminBillingEmail'] ?? '') ?: (string) (Yii::$app->params['adminEmail'] ?? '');
        if ($billing === '') {
            return;
        }
        Yii::$app->mailer->compose(
            ['html' => '@common/mail/customer-message-html', 'text' => '@common/mail/customer-message-text'],
            ['subject' => $subject, 'body' => 'Sent to ' . count($sentTo) . ' customer(s): ' . implode(', ', $sentTo) . "\n\n----------\n\n" . $body, 'button' => ['Admin', (string) (Yii::$app->params['backendBaseUrl'] ?? '')]],
        )
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($billing)
            ->setSubject('[Copy] ' . $subject)
            ->send();
    }
}
