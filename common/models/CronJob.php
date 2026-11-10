<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveRecord;

/**
 * The admin on/off switch of a cron job. Cron still starts a job that is off; it stops at once
 * (CronController, BackupController): PHP can't edit the crontab.
 *
 * @property string $job
 * @property int $is_enabled
 * @property string|null $updated_by
 * @property int|null $updated_at
 */
final class CronJob extends ActiveRecord
{
    /** Route => [name, when it runs, what stops while it is off]. */
    public const JOBS = [
        'cron/run-billing' => ['Billing and invoices', 'Daily', 'No renewal invoices are created or emailed.'],
        'cron/payment-reminders' => ['Payment reminders', 'Daily', 'Customers get no reminders about unpaid invoices.'],
        'cron/send-reminders' => ['Renewal reminders', 'Daily', 'Customers and staff get no expiry reminders.'],
        'cron/profile-reminders' => ['Incomplete profiles', 'Daily', 'Nobody is told about incomplete billing profiles, and inactive accounts are not disabled.'],
        'cron/razorpay-sync' => ['Online payment sync (Razorpay and PayPal)', 'Every 30 minutes', 'A PayPal payment held by PayPal is never recorded when PayPal completes it (the invoice stays unpaid although the client paid). A Razorpay or PayPal payment whose browser was lost stays unrecorded until the customer tries to pay that invoice again, Razorpay fees are not filled in, and refunds made in the Razorpay dashboard are not noticed.'],
        'cron/whatsapp-cleanup' => ['WhatsApp log cleanup', 'Weekly', 'Old WhatsApp log rows stay.'],
        'backup/run' => ['Daily backup', 'Daily', 'No scheduled database or payment proof backups. The backup before a deploy still runs.'],
    ];

    public static function tableName(): string
    {
        return '{{%cron_job}}';
    }

    public static function primaryKey(): array
    {
        return ['job'];
    }

    /** No row = on. If the switch can't be read the job runs: it reports its own failure, while a silent stop would go unnoticed. */
    public static function isEnabled(string $job): bool
    {
        try {
            $enabled = self::find()->select('is_enabled')->where(['job' => $job])->scalar();
        } catch (\Throwable $e) {
            \Yii::error($e, __METHOD__);

            return true;
        }

        return $enabled === false || $enabled === null || (bool) $enabled;
    }

    public static function set(string $job, bool $enabled, string $by): void
    {
        $model = self::findOne($job) ?? new self(['job' => $job]);
        $model->is_enabled = (int) $enabled;
        $model->updated_by = $by;
        $model->updated_at = time();
        if (!$model->save(false)) {
            throw new \RuntimeException('The cron job switch could not be saved.');
        }
    }
}
