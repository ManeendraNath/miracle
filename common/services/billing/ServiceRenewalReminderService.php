<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingSettings;
use common\models\CronJob;
use common\models\CustomerBillingProfile;
use common\models\CustomerPreference;
use common\models\Domains;
use common\models\Hosting;
use common\models\InvoiceItem;
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
    private const ITEM_TYPES = ['domain' => InvoiceItem::TYPE_DOMAIN, 'hosting' => InvoiceItem::TYPE_HOSTING, 'maintenance' => InvoiceItem::TYPE_MAINTENANCE];

    /** Reminders and staff alerts that failed in the last run. */
    public int $failures = 0;

    /** Services due a staff alert in this run, sent together as one email by sendStaffDigest(). @var array<int, array<string, mixed>> */
    private array $staffDue = [];

    public function sendReminders(?DateTimeImmutable $today = null): int
    {
        $today ??= new DateTimeImmutable('today');
        $count = 0;
        $count += $this->processDomains($today);
        $count += $this->processHosting($today);
        $count += $this->processMaintenance($today);
        $this->sendStaffDigest($today);
        return $count;
    }

    private function processDomains(DateTimeImmutable $today): int
    {
        $sent = 0;
        $domains = Domains::find()->where(['status' => Domains::STATUS_ACTIVE])->andWhere(['not in', 'user_id', \common\models\User::deletedIds()])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($domains as $domain) {
            $serviceProcessed = $this->processService('domain', (int) $domain->id, (int) $domain->user_id, (string) $domain->domain_url, (string) $domain->current_expiry_date, $today, (int) $domain->created_at, (float) $domain->renewal_base_amount > 0 || (float) $domain->next_renewal_amount > 0);
            if ($serviceProcessed) {
                $sent++;
            }
        }
        return $sent;
    }

    private function processHosting(DateTimeImmutable $today): int
    {
        $sent = 0;
        $hostings = Hosting::find()->where(['status' => Hosting::STATUS_ACTIVE])->andWhere(['not in', 'user_id', \common\models\User::deletedIds()])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($hostings as $hosting) {
            $serviceProcessed = $this->processService('hosting', (int) $hosting->id, (int) $hosting->user_id, (string) $hosting->primary_domain, (string) $hosting->current_expiry_date, $today, (int) $hosting->created_at, (float) $hosting->renewal_base_amount > 0 || (float) $hosting->next_renewal_amount > 0);
            if ($serviceProcessed) {
                $sent++;
            }
        }
        return $sent;
    }

    private function processMaintenance(DateTimeImmutable $today): int
    {
        $sent = 0;
        $maintenances = Maintenance::find()->where(['status' => Maintenance::STATUS_ACTIVE])->andWhere(['not in', 'user_id', \common\models\User::deletedIds()])->andWhere(['>=', 'current_expiry_date', $today->format('Y-m-d')])->all();
        foreach ($maintenances as $maintenance) {
            $serviceProcessed = $this->processService('maintenance', (int) $maintenance->id, (int) $maintenance->user_id, (string) $maintenance->website_url, (string) $maintenance->current_expiry_date, $today, (int) $maintenance->created_at, (float) $maintenance->renewal_base_amount > 0 || (float) $maintenance->next_renewal_amount > 0);
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
        // As InvoicePaymentService::renewalState(): no invoice is coming for these, so don't promise one.
        $itemType = self::ITEM_TYPES[$type];
        $billable = $hasPrice && (bool) BillingSettings::forType($itemType)->is_active && CustomerBillingProfile::find()->where(['user_id' => $userId])->exists() && CronJob::isEnabled('cron/run-billing');

        return $this->sendReminder($type, $serviceId, $userId, $serviceName, $expiryDate, $daysUntilExpiry, $threshold, $billable);
    }

    /**
     * The reminder day due now: today's, or one missed in the last 2 days (the cron skips runs during a
     * deploy). Older misses are not caught up, and a missed day is only caught up if the invoice or service
     * existed before it, so one created on or just after a reminder day doesn't get it straight away.
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
            && $createdAt >= $target->modify(sprintf('%+d days', -$threshold))->getTimestamp()) {
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
                $this->staffDue[] = compact('type', 'serviceId', 'userId', 'serviceName', 'expiryDate', 'daysBefore', 'hasPrice', 'key');
            }
        } catch (\Throwable $e) {
            $this->failures++;
            Yii::error(['message' => 'Staff expiry alert failed.', 'serviceType' => $type, 'serviceId' => $serviceId, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    private function sendReminder(string $type, int $serviceId, int $userId, string $serviceName, string $expiryDate, int $daysBefore, int $threshold, bool $billable): bool
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
                                'billable' => $billable,
                            ]
                    )
                    ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                    ->setTo($client->email)
                    ->setSubject(sprintf('Renewal Reminder: %s', $serviceName));
            AdminMailHelper::addBillingBcc($message);
            $emailSent = $message->send();
            if (!$emailSent) {
                $this->failures++;
                Yii::warning(['serviceType' => $type, 'serviceId' => $serviceId, 'userId' => $userId, 'email' => $client->email], __METHOD__);
                return false;
            }

            $this->log($type, $serviceId, $userId, $key, $daysBefore);
            // After the log row, so a failed send doesn't re-create these on every cron run.
            (new NotificationService())->create($userId, 'renewal', 'Renewal Reminder', $daysBefore === 0 ? $serviceName . ' expires today.' : sprintf('%s expires in %d day(s).', $serviceName, $daysBefore), '/renewal/index');
            return true;
        } catch (\Throwable $e) {
            $this->failures++;
            Yii::error(['serviceType' => $type, 'serviceId' => $serviceId, 'error' => $e->getMessage()], __METHOD__);
            return false;
        }
    }

    /** One email for everything due an alert today, grouped by customer, with each service's price or invoice. */
    private function sendStaffDigest(DateTimeImmutable $today): void
    {
        if ($this->staffDue === []) {
            return;
        }
        try {
            $byCustomer = [];
            foreach ($this->staffDue as $due) {
                $byCustomer[$due['userId']][] = $due;
            }
            $blocks = [];
            foreach ($byCustomer as $userId => $services) {
                $user = User::findOne($userId);
                $profile = CustomerBillingProfile::findOne(['user_id' => $userId]);
                $lines = [sprintf('Customer: %s (%s)', $profile?->legal_name ?: ($user?->username ?? '#' . $userId), $user?->email ?? 'no email')];
                foreach ($services as $due) {
                    try {
                        $lines[] = '- ' . $this->digestLine($due);
                    } catch (\Throwable $e) {
                        Yii::error(['message' => 'Expiry digest line failed.', 'serviceType' => $due['type'], 'serviceId' => $due['serviceId'], 'error' => $e->getMessage()], __METHOD__);
                        $lines[] = sprintf('- %s %s: expires %s. Price could not be worked out, check the service.', ucfirst($due['type']), $due['serviceName'], $due['expiryDate']);
                    }
                }
                $blocks[] = implode("\n", $lines);
            }
            AdminAlertService::servicesExpiring(count($this->staffDue), implode("\n\n", $blocks));
            foreach ($this->staffDue as $due) {
                $this->log($due['type'], $due['serviceId'], $due['userId'], $due['key'], $due['daysBefore']);
            }
        } catch (\Throwable $e) {
            $this->failures++;
            Yii::error(['message' => 'Staff expiry digest failed.', 'error' => $e->getMessage()], __METHOD__);
        }
        $this->staffDue = [];
    }

    /** @param array<string, mixed> $due */
    private function digestLine(array $due): string
    {
        $labels = ['domain' => 'Domain', 'hosting' => 'Hosting', 'maintenance' => 'Maintenance'];
        $when = $due['daysBefore'] === 0 ? 'today' : ($due['daysBefore'] === 1 ? 'tomorrow' : 'in ' . $due['daysBefore'] . ' days');
        $line = sprintf('%s %s: expires %s (%s). ', $labels[$due['type']], $due['serviceName'], date('d M Y', (int) strtotime($due['expiryDate'])), $when);
        $itemType = self::ITEM_TYPES[$due['type']];
        $money = static fn(float $amount): string => '₹' . number_format($amount, 2);

        $event = \common\models\BillingEvent::find()
            ->where(['item_type' => $itemType, 'item_id' => $due['serviceId']])
            ->andWhere(['>', 'period_start', $due['expiryDate']])
            ->andWhere(['<>', 'status', \common\models\BillingEvent::STATUS_CANCELLED])
            ->orderBy(['period_start' => SORT_ASC])
            ->one();
        $invoice = $event?->invoice_id ? \common\models\Invoice::findOne($event->invoice_id) : null;
        if ($invoice !== null) {
            return $line . sprintf('Renewal %s on invoice %s (%s, invoice total %s).', $money((float) $event->amount), $invoice->reference(), $invoice->statusLabel(), $money((float) $invoice->total_payable));
        }
        if (!$due['hasPrice']) {
            return $line . 'No renewal price is set, so no invoice will be generated. Set the Renewal Base Amount.';
        }
        $service = ['domain' => Domains::class, 'hosting' => Hosting::class, 'maintenance' => Maintenance::class][$due['type']]::findOne($due['serviceId']);
        if ($service === null) {
            return $line;
        }
        if ($service->next_renewal_amount !== null) {
            return $line . 'Renewal ' . $money((float) $service->next_renewal_amount) . ' (Next Renewal Price). No invoice yet.';
        }
        $settings = BillingSettings::forType($itemType);
        $price = (new RenewalPriceCalculator())->calculate(
            $itemType,
            (int) $service->id,
            (float) $service->renewal_base_amount,
            $service->renewal_increase_percent !== null ? (float) $service->renewal_increase_percent : (float) $settings->renewal_increase_percent,
            $service->renewal_cycle_months !== null ? (int) $service->renewal_cycle_months : (int) $settings->renewal_cycle_months
        );

        return $line . 'Renewal about ' . $money($price->amount) . '. No invoice yet.';
    }
}
