<?php

declare(strict_types=1);

namespace common\services\customer;

use common\models\CustomerBillingProfile;
use common\models\CustomerNotification;
use common\models\Domains;
use common\models\Hosting;
use common\models\Invoice;
use common\models\Maintenance;
use common\models\SupportTicket;
use common\models\Transaction;
use common\models\User;
use common\services\admin\AdminAlertService;
use common\services\notification\NotificationService;
use Yii;

/**
 * Daily reminder to every active customer whose billing profile is missing or incomplete, from the day after their
 * account was created until it is complete. Without a profile no renewal invoice can be created.
 * Not subject to the reminder preferences: this is needed for billing.
 *
 * A month on, an account still incomplete with nothing live (see customersInUse()) is suspended, and the customer told,
 * but only after at least NOTICE_DAYS of warnings. Open invoices count because a suspended customer can't log in to pay
 * them. Staff can set it Active again, which gives another month.
 */
final class IncompleteProfileReminderService
{
    public const TITLE = 'Please Complete Your Billing Details';
    private const NOTICE_DAYS = 7;
    private const DIGEST_LINES = 200;
    private const REQUIRED = ['legal_name' => 'legal name', 'address_line_1' => 'address', 'city' => 'city', 'state' => 'state', 'postal_code' => 'postal code'];

    /** Customers who could not be reminded in the last run. */
    public int $failures = 0;
    /** Accounts suspended in the last run. */
    public int $disabled = 0;

    /** @return int customers reminded */
    public function sendReminders(): int
    {
        $sent = 0;
        $incomplete = [];
        $suspended = [];
        $disableBefore = (int) strtotime('-1 month');
        $inUse = self::customersInUse();
        $startOfToday = (int) strtotime('today');
        $customers = User::find()->where(['status' => User::getStatusActive()])->andWhere(['<=', 'created_at', time() - 86400])->orderBy(['id' => SORT_ASC])->all();
        $profiles = CustomerBillingProfile::find()->where(['user_id' => array_map(static fn(User $user): int => (int) $user->id, $customers)])->indexBy('user_id')->all();
        foreach ($customers as $customer) {
            $missing = self::missingFields($profiles[$customer->id] ?? null);
            if ($missing === []) {
                continue;
            }
            $line = sprintf('%s (%s): %s', $customer->username, $customer->email, implode(', ', $missing));
            $canBeDisabled = !isset($inUse[(int) $customer->id]);
            $warnedAt = $customer->profile_warned_at === null ? null : (int) $customer->profile_warned_at;
            try {
                // A warning only counts for a stretch with nothing live: the warnings were not sent while there was.
                if (!$canBeDisabled && $warnedAt !== null) {
                    $customer->updateAttributes(['profile_warned_at' => null]);
                }
                // reactivated_at too: staff setting an account Active again must not see it suspended the next morning.
                if (
                    $canBeDisabled && $warnedAt !== null && $warnedAt <= time() - self::NOTICE_DAYS * 86400
                    && max((int) $customer->created_at, (int) $customer->reactivated_at) <= $disableBefore
                ) {
                    $this->disable($customer);
                    $suspended[] = $line;
                    continue;
                }
                $incomplete[] = $line;
                if ($this->remind($customer, $missing, $startOfToday, $canBeDisabled)) {
                    $sent++;
                    if ($canBeDisabled && $warnedAt === null) {
                        $customer->updateAttributes(['profile_warned_at' => time()]);
                    }
                }
            } catch (\Throwable $e) {
                $this->failures++;
                Yii::error(['message' => 'Incomplete profile reminder failed.', 'userId' => (int) $customer->id, 'error' => $e->getMessage()], __METHOD__);
            }
        }
        if ($incomplete !== []) {
            AdminAlertService::profilesIncomplete(self::capped($incomplete));
        }
        if ($suspended !== []) {
            AdminAlertService::accountsDisabled(self::capped($suspended), count($suspended));
        }

        return $sent;
    }

    /** @return string[] what is missing; empty when complete */
    public static function missingFields(?CustomerBillingProfile $profile): array
    {
        if ($profile === null) {
            return ['no billing details yet'];
        }

        return array_values(array_filter(self::REQUIRED, static fn(string $label, string $attribute): bool => trim((string) $profile->$attribute) === '', ARRAY_FILTER_USE_BOTH));
    }

    /** @param bool $countTickets false for the portal gate: opening a ticket must not unlock the portal */
    public static function isInUse(int $userId, bool $countTickets = true): bool
    {
        return self::customersInUse($userId, $countTickets) !== [];
    }

    /** One bell row per customer: read once the details are complete, however they got there. */
    public static function clearReminder(int $userId): void
    {
        CustomerNotification::updateAll(['is_read' => 1, 'read_at' => time()], ['user_id' => $userId, 'title' => self::TITLE, 'is_read' => 0]);
        User::updateAll(['profile_warned_at' => null], ['id' => $userId]);
    }

    /**
     * Customers who still need the portal, so must not be disabled or kept out of it: a live service, an open invoice,
     * an open ticket, or a payment held for refund or apply.
     *
     * @return array<int, true>
     */
    private static function customersInUse(?int $userId = null, bool $countTickets = true): array
    {
        $ids = Invoice::find()->select('user_id')->where(['status' => [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID]])->andFilterWhere(['user_id' => $userId])->column();
        foreach ([Domains::class, Hosting::class, Maintenance::class] as $service) {
            $ids = array_merge($ids, $service::find()->select('user_id')->where(['status' => $service::RENEWABLE_STATUSES])->andFilterWhere(['user_id' => $userId])->column());
        }
        if ($countTickets) {
            $ids = array_merge($ids, SupportTicket::find()->select('user_id')->where(['status' => [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS]])->andFilterWhere(['user_id' => $userId])->column());
        }
        $ids = array_merge(
            $ids,
            Invoice::find()->alias('i')->select('i.user_id')->innerJoin(['t' => Transaction::tableName()], 't.invoice_id = i.id')
                ->where(['t.status' => Transaction::STATUS_CAPTURED, 't.is_unapplied' => 1])->andFilterWhere(['i.user_id' => $userId])->column(),
        );

        return array_fill_keys(array_map('intval', $ids), true);
    }

    /**
     * @param string[] $lines
     * @return string[] short enough for an email and the bell
     */
    private static function capped(array $lines): array
    {
        $extra = count($lines) - self::DIGEST_LINES;

        return $extra > 0 ? [...array_slice($lines, 0, self::DIGEST_LINES), "+{$extra} more"] : $lines;
    }

    private function disable(User $customer): void
    {
        // Only these columns: an older record failing today's rules must not block it.
        $customer->generateAuthKey(); // a remember-me cookie must not come back to life if it is reactivated
        $customer->updateAttributes(['status' => User::getStatusSuspended(), 'auth_key' => $customer->auth_key, 'updated_at' => time()]);
        $this->disabled++;
        // Suspended either way: the admin list names it, so a failed email doesn't lose track of it.
        try {
            $sent = Yii::$app->mailer
                ->compose(['html' => '@common/mail/account-disabled-html', 'text' => '@common/mail/account-disabled-text'], ['client' => $customer])
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setTo($customer->email)
                ->setSubject('Your account has been disabled')
                ->send();
            if (!$sent) {
                throw new \RuntimeException('The email could not be sent.');
            }
        } catch (\Throwable $e) {
            $this->failures++;
            Yii::error(['message' => 'Account disabled email failed.', 'userId' => (int) $customer->id, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    private function remind(User $customer, array $missing, int $startOfToday, bool $canBeDisabled): bool
    {
        // One bell row per customer, kept current: a daily reminder must not pile up unread rows.
        $notification = CustomerNotification::find()->where(['user_id' => (int) $customer->id, 'title' => self::TITLE])->orderBy(['id' => SORT_DESC])->one();
        // Already reminded today, e.g. the job was run twice.
        if ($notification !== null && (int) $notification->created_at >= $startOfToday) {
            return false;
        }
        $sent = Yii::$app->mailer
            ->compose(['html' => '@common/mail/profile-incomplete-html', 'text' => '@common/mail/profile-incomplete-text'], ['client' => $customer, 'missing' => $missing, 'canBeDisabled' => $canBeDisabled])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($customer->email)
            ->setSubject(self::TITLE)
            ->send();
        if (!$sent) {
            throw new \RuntimeException('The email could not be sent.');
        }
        if ($notification !== null && !(bool) $notification->is_read) {
            $notification->updateAttributes(['created_at' => time()]);
        } else {
            (new NotificationService())->create((int) $customer->id, 'account', self::TITLE, 'Your invoices need your complete billing details.', '/account/profile');
        }

        return true;
    }
}
