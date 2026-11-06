<?php

declare(strict_types=1);

namespace common\services\customer;

use common\models\CustomerBillingProfile;
use common\models\CustomerNotification;
use common\models\User;
use common\services\admin\AdminAlertService;
use common\services\notification\NotificationService;
use Yii;

/**
 * Daily reminder to every active customer whose billing profile is missing or incomplete, from the day after their
 * account was created until it is complete. Without a profile no renewal invoice can be created.
 * Not subject to the reminder preferences: this is needed for billing.
 */
final class IncompleteProfileReminderService
{
    public const TITLE = 'Please Complete Your Billing Details';
    private const REQUIRED = ['legal_name' => 'legal name', 'address_line_1' => 'address', 'city' => 'city', 'state' => 'state', 'postal_code' => 'postal code'];

    /** Customers who could not be reminded in the last run. */
    public int $failures = 0;

    /** @return int customers reminded */
    public function sendReminders(): int
    {
        $sent = 0;
        $incomplete = [];
        $startOfToday = (int) strtotime('today');
        $customers = User::find()->where(['status' => User::getStatusActive()])->andWhere(['<=', 'created_at', time() - 86400])->orderBy(['id' => SORT_ASC])->all();
        $profiles = CustomerBillingProfile::find()->where(['user_id' => array_map(static fn(User $user): int => (int) $user->id, $customers)])->indexBy('user_id')->all();
        foreach ($customers as $customer) {
            $missing = self::missingFields($profiles[$customer->id] ?? null);
            if ($missing === []) {
                continue;
            }
            $incomplete[] = sprintf('%s (%s): %s', $customer->username, $customer->email, implode(', ', $missing));
            try {
                if ($this->remind($customer, $missing, $startOfToday)) {
                    $sent++;
                }
            } catch (\Throwable $e) {
                $this->failures++;
                Yii::error(['message' => 'Incomplete profile reminder failed.', 'userId' => (int) $customer->id, 'error' => $e->getMessage()], __METHOD__);
            }
        }
        if ($incomplete !== []) {
            AdminAlertService::profilesIncomplete($incomplete);
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

    private function remind(User $customer, array $missing, int $startOfToday): bool
    {
        // One bell row per customer, kept current: a daily reminder must not pile up unread rows.
        $notification = CustomerNotification::find()->where(['user_id' => (int) $customer->id, 'title' => self::TITLE])->orderBy(['id' => SORT_DESC])->one();
        // Already reminded today, e.g. the job was run twice.
        if ($notification !== null && (int) $notification->created_at >= $startOfToday) {
            return false;
        }
        $sent = Yii::$app->mailer
            ->compose(['html' => '@common/mail/profile-incomplete-html', 'text' => '@common/mail/profile-incomplete-text'], ['client' => $customer, 'missing' => $missing])
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
            (new NotificationService())->create((int) $customer->id, 'account', self::TITLE, 'We need your complete billing details to issue your invoices.', '/account/profile');
        }

        return true;
    }
}
