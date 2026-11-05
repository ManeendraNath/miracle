<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerPreference;
use common\models\Invoice;
use common\models\User;
use common\services\mail\AdminMailHelper;
use common\services\notification\WhatsAppService;
use DateTimeImmutable;
use Yii;
use yii\db\IntegrityException;

final class InvoiceReminderService
{
    private const REMINDER_DAYS = [30, 15, 7, 5, 4, 3, 2, 1, 0, -1, -3, -7, -15, -30];

    /** Reminders that failed in the last run. */
    public int $failures = 0;

    public function sendDueReminders(?DateTimeImmutable $today = null): int
    {
        $today ??= new DateTimeImmutable('today');
        $invoices = Invoice::find()
                ->where(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID],])
                ->andWhere(['not in', 'user_id', \common\models\User::deletedIds()])
                ->andWhere(['not', ['due_date' => null]])
                ->orderBy(['due_date' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        $paymentService = new InvoicePaymentService();
        $sentCount = 0;
        foreach ($invoices as $invoice) {
            try {
                $outstanding = $paymentService->getOutstanding($invoice);
                // Under review: if the proof is rejected, the 2-day catch-up sends this reminder then.
                if ($outstanding <= 0 || $paymentService->hasPendingProof((int) $invoice->id)) {
                    continue;
                }
                $dueDate = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $invoice->due_date);
                if ($dueDate === false) {
                    Yii::warning(['message' => 'Skipping reminder because invoice due date is invalid.', 'invoiceId' => (int) $invoice->id, 'dueDate' => $invoice->due_date,
                            ], __METHOD__);
                    continue;
                }
                $daysUntilDue = (int) $today->diff($dueDate)->format('%r%a');
                $threshold = ServiceRenewalReminderService::dueThreshold($daysUntilDue, self::REMINDER_DAYS, $dueDate, (int) $invoice->created_at);
                if ($threshold === null) {
                    continue;
                }
                $preferences = CustomerPreference::forUser((int) $invoice->user_id);
                if (!(bool) $preferences->payment_reminders) {
                    continue;
                }
                if ($this->sendReminder($invoice, $outstanding, $daysUntilDue, $threshold)) {
                    $sentCount++;
                }
            } catch (\Throwable $e) {
                $this->failures++;
                Yii::error(['message' => 'Invoice reminder failed.', 'invoiceId' => (int) $invoice->id, 'error' => $e->getMessage()], __METHOD__);
            }
        }
        return $sentCount;
    }

    private function sendReminder(Invoice $invoice, float $outstanding, int $daysUntilDue, int $threshold): bool
    {
        // Due date is part of the key so a changed due date gets its own reminder schedule.
        // Keyed by the reminder day, so a catch-up after a skipped run isn't repeated the next day.
        $key = $this->getReminderKey($threshold) . '|' . $invoice->due_date;
        $alreadySent = Yii::$app->db
                ->createCommand('SELECT id FROM {{%invoice_reminder_log}} WHERE invoice_id = :invoiceId AND reminder_key = :reminderKey LIMIT 1')
                ->bindValues([':invoiceId' => (int) $invoice->id, ':reminderKey' => $key])
                ->queryScalar();
        if ($alreadySent !== false) {
            return false;
        }
        $client = User::findOne((int) $invoice->user_id);
        if ($client === null || empty($client->email)) {
            throw new \DomainException('Invoice customer does not have a delivery email address.');
        }
        $invoiceUrl = $this->getInvoiceUrl($invoice);
        $subject = $this->getSubject($invoice, $daysUntilDue);
        $message = Yii::$app->mailer
                ->compose(
                    [
                            'html' => '@common/mail/payment-reminder-html',
                            'text' => '@common/mail/payment-reminder-text',
                        ],
                    [
                            'invoice' => $invoice,
                            'outstandingAmount' => $outstanding,
                            'daysUntilDue' => $daysUntilDue,
                            'invoiceUrl' => $invoiceUrl,
                        ]
                )
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($client->email)
                ->setSubject($subject);
        AdminMailHelper::addBillingBcc($message);
        if (!$message->send()) {
            throw new \RuntimeException('Payment reminder email could not be sent.');
        }

        try {
            Yii::$app->db->createCommand()->insert(
                '{{%invoice_reminder_log}}',
                [
                                'invoice_id' => (int) $invoice->id,
                                'reminder_key' => $key,
                                'days_from_due' => $daysUntilDue,
                                'recipient_email' => $client->email,
                                'sent_at' => time(),
                                'created_at' => time(),
                            ]
            )
                    ->execute();
            $components = [
                'body_1' => [
                    'type' => 'text',
                    'value' => $client->username,
                ],
                'body_2' => [
                    'type' => 'text',
                    'value' => $invoice->invoice_number,
                ],
            ];
            WhatsAppService::sendTemplate($client->getWhatsappNumber(), 'invoice_reminder', $components, 'invoice-reminder-' . $invoice->id . '-' . $daysUntilDue . 'days');
        } catch (IntegrityException) {
            return false;
        }

        return true;
    }

    private function getReminderKey(int $daysUntilDue): string
    {
        if ($daysUntilDue > 0) {
            return 'before_' . $daysUntilDue;
        }
        if ($daysUntilDue === 0) {
            return 'due_today';
        }
        return 'overdue_' . abs($daysUntilDue);
    }

    private function getSubject(Invoice $invoice, int $daysUntilDue): string
    {
        if ($daysUntilDue > 0) {
            return sprintf('Payment reminder: %d day%s left for invoice %s', $daysUntilDue, $daysUntilDue === 1 ? '' : 's', $invoice->invoice_number);
        }
        if ($daysUntilDue === 0) {
            return 'Payment due today: ' . $invoice->invoice_number;
        }
        return sprintf('Payment overdue: invoice %s', $invoice->invoice_number);
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
