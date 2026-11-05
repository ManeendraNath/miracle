<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerPreference;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\ServiceReminderLog;
use common\models\User;
use common\services\admin\AdminAlertService;
use common\services\mail\AdminMailHelper;
use common\services\notification\NotificationService;
use DateTimeImmutable;
use Yii;

final class ServiceRenewalReminderService
{
    private const REMINDER_DAYS = [60, 30, 15, 7, 3, 1];

    public function sendReminders(?DateTimeImmutable $today = null): int
    {
        $today ??= new DateTimeImmutable('today');
        $count = 0;
        $count += $this->processDomains($today);
        $count += $this->processHosting($today);
        $count += $this->processMaintenance($today);
        return $count;
    }

    private function processDomains(DateTimeImmutable $today): int
    {
        $sent = 0;
        $domains = Domains::find()->where(['status' => Domains::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($domains as $domain) {
            $serviceProcessed = $this->processService('domain', (int) $domain->id, (int) $domain->user_id, (string) $domain->domain_url, (string) $domain->current_expiry_date, $today, (int) $domain->created_at, (float) $domain->renewal_base_amount > 0);
            if ($serviceProcessed) {
                $sent++;
            }
        }
        return $sent;
    }

    private function processHosting(DateTimeImmutable $today): int
    {
        $sent = 0;
        $hostings = Hosting::find()->where(['status' => Hosting::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($hostings as $hosting) {
            $serviceProcessed = $this->processService('hosting', (int) $hosting->id, (int) $hosting->user_id, (string) $hosting->primary_domain, (string) $hosting->current_expiry_date, $today, (int) $hosting->created_at, (float) $hosting->renewal_base_amount > 0);
            if ($serviceProcessed) {
                $sent++;
            }
        }
        return $sent;
    }

    private function processMaintenance(DateTimeImmutable $today): int
    {
        $sent = 0;
        $maintenances = Maintenance::find()->where(['status' => Maintenance::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($maintenances as $maintenance) {
            $serviceProcessed = $this->processService('maintenance', (int) $maintenance->id, (int) $maintenance->user_id, (string) $maintenance->website_url, (string) $maintenance->current_expiry_date, $today, (int) $maintenance->created_at, (float) $maintenance->renewal_base_amount > 0);
            if ($serviceProcessed) {
                $sent++;
            }
        }
        return $sent;
    }

    private function processService(string $type, int $serviceId, int $userId, string $serviceName, string $expiryDate, DateTimeImmutable $today, int $createdAt = 0, bool $hasPrice = true): bool
    {
        $expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);
        if (!$expiry) {
            return false;
        }
        $daysUntilExpiry = (int) $today->diff($expiry)->format('%r%a');
        $threshold = self::dueThreshold($daysUntilExpiry, self::REMINDER_DAYS, $expiry, $createdAt);
        if ($threshold === null) {
            return false;
        }
        // Staff hear about it whatever the customer's email preference.
        $this->alertStaff($type, $serviceId, $userId, $serviceName, $expiryDate, $threshold, $daysUntilExpiry, $hasPrice);
        $preferences = CustomerPreference::forUser($userId);
        if (!(bool) $preferences->renewal_reminders) {
            return false;
        }
        return $this->sendReminder($type, $serviceId, $userId, $serviceName, $expiryDate, $daysUntilExpiry, $threshold);
    }

    /**
     * The reminder day due now: today's, or one missed in the last 2 days (the cron skips runs during a
     * deploy). Older misses are not caught up, and a missed day is only caught up if the invoice or service
     * already existed on it, so one created just after a reminder day doesn't get it on day one.
     *
     * @param int[] $reminderDays
     * @param DateTimeImmutable|null $target the due/expiry date the days count down to
     */
    public static function dueThreshold(int $days, array $reminderDays, ?DateTimeImmutable $target = null, int $createdAt = 0): ?int
    {
        $due = array_filter($reminderDays, static fn(int $threshold): bool => $threshold >= $days && $threshold - $days <= 2);
        if ($due === []) {
            return null;
        }
        $threshold = min($due);
        if ($threshold !== $days && $target !== null && $createdAt > 0
            && $createdAt >= $target->modify(sprintf('%+d days', -$threshold + 1))->getTimestamp()) {
            return null;
        }

        return $threshold;
    }

    private function logged(string $type, int $serviceId, string $key): bool
    {
        return ServiceReminderLog::find()->where(['service_type' => $type, 'service_id' => $serviceId, 'reminder_key' => $key])->exists();
    }

    private function log(string $type, int $serviceId, int $userId, string $key, int $daysBefore): void
    {
        $log = new ServiceReminderLog();
        $log->service_type = $type;
        $log->service_id = $serviceId;
        $log->user_id = $userId;
        $log->reminder_key = $key;
        $log->days_before = $daysBefore;
        $log->sent_at = time();
        $log->created_at = time();
        $log->save(false);
    }

    private function alertStaff(string $type, int $serviceId, int $userId, string $serviceName, string $expiryDate, int $threshold, int $daysBefore, bool $hasPrice): void
    {
        $key = 'staff_' . $threshold . '@' . $expiryDate;
        try {
            if (!$this->logged($type, $serviceId, $key)) {
                AdminAlertService::serviceExpiring($serviceName, $expiryDate, $hasPrice);
                $this->log($type, $serviceId, $userId, $key, $daysBefore);
            }
        } catch (\Throwable $e) {
            Yii::error(['message' => 'Staff expiry alert failed.', 'serviceType' => $type, 'serviceId' => $serviceId, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    private function sendReminder(string $type, int $serviceId, int $userId, string $serviceName, string $expiryDate, int $daysBefore, int $threshold): bool
    {
        try {
            // Expiry date in the key so each renewal cycle gets its own reminders.
            $key = 'before_' . $threshold . '@' . $expiryDate;
            if ($this->logged($type, $serviceId, $key)) {
                return false;
            }
            $client = User::findOne($userId);
            if ($client === null || empty($client->email)) {
                return false;
            }
            $message = Yii::$app->mailer
                    ->compose(
                        [
                                'html' => '@common/mail/renewal-reminder-html',
                                'text' => '@common/mail/renewal-reminder-text',
                            ],
                        [
                                'serviceName' => $serviceName,
                                'expiryDate' => $expiryDate,
                                'daysBefore' => $daysBefore,
                                'client' => $client,
                            ]
                    )
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($client->email)
                    ->setSubject(sprintf('Renewal Reminder: %s', $serviceName));
            AdminMailHelper::addBillingBcc($message);
            $emailSent = $message->send();
            if (!$emailSent) {
                Yii::warning(['serviceType' => $type, 'serviceId' => $serviceId, 'userId' => $userId, 'email' => $client->email], __METHOD__);
                return false;
            }

            $this->log($type, $serviceId, $userId, $key, $daysBefore);
            // After the log row, so a failed send doesn't re-create these on every cron run.
            (new NotificationService())->create($userId, 'renewal', 'Renewal Reminder', sprintf('%s expires in %d day(s).', $serviceName, $daysBefore), '/renewal/index');
            return true;
        } catch (\Throwable $e) {
            Yii::error(['serviceType' => $type, 'serviceId' => $serviceId, 'error' => $e->getMessage()], __METHOD__);
            return false;
        }
    }
}
