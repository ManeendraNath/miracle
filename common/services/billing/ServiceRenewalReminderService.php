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
use common\services\notification\NotificationService;
use DateTimeImmutable;
use Yii;

final class ServiceRenewalReminderService
{

    private const REMINDER_DAYS = [
        60,
        30,
        15,
        7,
        3,
        1,
    ];

    public function sendReminders(
            ?DateTimeImmutable $today = null
    ): int
    {

        $today ??= new DateTimeImmutable(
                'today'
        );

        $count = 0;

        $count += $this->processDomains($today);

        $count += $this->processHosting($today);

        $count += $this->processMaintenance($today);

        return $count;
    }

    private function processDomains(
            DateTimeImmutable $today
    ): int
    {

        $sent = 0;

        $domains = Domains::find()->where(['status' => Domains::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', date('Y-m-d')])->all();
        foreach ($domains as $domain) {

            if (
                    $this->processService(
                            'domain',
                            (int) $domain->id,
                            (int) $domain->user_id,
                            (string) $domain->domain_url,
                            (string) $domain->current_expiry_date,
                            $today
                    )
            ) {
                $sent++;
            }
        }

        return $sent;
    }

    private function processHosting(
            DateTimeImmutable $today
    ): int
    {

        $sent = 0;

        $hostings = Hosting::find()->where(['status' => Hosting::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', date('Y-m-d')])->all();
        foreach ($hostings as $hosting) {

            if (
                    $this->processService(
                            'hosting',
                            (int) $hosting->id,
                            (int) $hosting->user_id,
                            (string) $hosting->primary_domain,
                            (string) $hosting->current_expiry_date,
                            $today
                    )
            ) {
                $sent++;
            }
        }

        return $sent;
    }

    private function processMaintenance(
            DateTimeImmutable $today
    ): int
    {

        $sent = 0;

        $maintenances = Maintenance::find()->where(['status' => Maintenance::STATUS_ACTIVE])->andWhere(['>=', 'current_expiry_date', date('Y-m-d')])->all();
        foreach ($maintenances as $maintenance) {

            if (
                    $this->processService(
                            'maintenance',
                            (int) $maintenance->id,
                            (int) $maintenance->user_id,
                            (string) $maintenance->website_url,
                            (string) $maintenance->current_expiry_date,
                            $today
                    )
            ) {
                $sent++;
            }
        }

        return $sent;
    }

    private function processService(
            string $type,
            int $serviceId,
            int $userId,
            string $serviceName,
            string $expiryDate,
            DateTimeImmutable $today
    ): bool
    {

        $expiry = DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $expiryDate
        );

        if (!$expiry) {
            return false;
        }

        $daysUntilExpiry = (int) $today
                        ->diff($expiry)
                        ->format('%r%a');

        if (
                !in_array(
                        $daysUntilExpiry,
                        self::REMINDER_DAYS,
                        true
                )
        ) {
            return false;
        }

        $preferences = CustomerPreference::forUser(
                $userId
        );

        if (
                !(bool) $preferences
                ->renewal_reminders
        ) {
            return false;
        }

        return $this->sendReminder(
                        $type,
                        $serviceId,
                        $userId,
                        $serviceName,
                        $expiryDate,
                        $daysUntilExpiry
                );
    }

    private function sendReminder(
            string $type,
            int $serviceId,
            int $userId,
            string $serviceName,
            string $expiryDate,
            int $daysBefore
    ): bool
    {

        try {

            $key = 'before_' . $daysBefore;

            $alreadySent = ServiceReminderLog::find()
                    ->where([
                        'service_type' =>
                        $type,
                        'service_id' =>
                        $serviceId,
                        'reminder_key' =>
                        $key,
                    ])
                    ->exists();

            if ($alreadySent) {
                return false;
            }

            $client = User::findOne(
                    $userId
            );

            if (
                    $client === null || empty($client->email)
            ) {
                return false;
            }

            (new NotificationService())->create(
                    $userId,
                    'Renewal Reminder',
                    sprintf(
                            '%s expires in %d day(s).',
                            $serviceName,
                            $daysBefore
                    ),
                    '/renewal/index'
            );
            AdminAlertService::serviceExpiring(
                    $serviceName,
                    $expiryDate
            );

            $emailSent = Yii::$app->mailer
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
                    ->setTo($client->email)
                    ->setSubject(
                            sprintf(
                                    'Renewal Reminder: %s',
                                    $serviceName
                            )
                    )
                    ->send();

            if (!$emailSent) {
                Yii::warning(
                        [
                            'serviceType' => $type,
                            'serviceId' => $serviceId,
                            'userId' => $userId,
                            'email' => $client->email,
                        ],
                        __METHOD__
                );

                return false;
            }


            $log = new ServiceReminderLog();

            $log->service_type = $type;

            $log->service_id = $serviceId;

            $log->user_id = $userId;

            $log->reminder_key = $key;

            $log->days_before = $daysBefore;

            $log->sent_at = time();

            $log->created_at = time();

            $log->save(false);
            return true;
        } catch (\Throwable $e) {

            Yii::error(
                    [
                        'serviceType' => $type,
                        'serviceId' => $serviceId,
                        'error' => $e->getMessage(),
                    ],
                    __METHOD__
            );

            return false;
        }
    }
}
