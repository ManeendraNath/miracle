<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\CronJob;
use common\models\CustomerBillingProfile;
use common\models\CustomerNotification;
use common\models\User;
use common\services\admin\AdminAlertService;
use common\services\customer\IncompleteProfileReminderService;
use Yii;
use yii\db\Transaction as DbTransaction;

/** Incomplete profile reminders, emailed cron failures and the job switches. */
final class CronAlertsTest extends Unit
{
    protected $tester;
    private ?DbTransaction $_dbTransaction = null;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testAnIncompleteProfileIsRemindedOncePerDayAndNewCustomersWaitADay(): void
    {
        $noProfile = $this->customer(time() - 2 * 86400);
        $brandNew = $this->customer(time());
        $complete = $this->customer(time() - 2 * 86400);
        $this->profile($complete, 'Flat 1');
        $partial = $this->customer(time() - 2 * 86400);
        $this->profile($partial, '');

        (new IncompleteProfileReminderService())->sendReminders();

        $this->assertSame(1, $this->bell($noProfile));
        $this->assertSame(1, $this->bell($partial));
        $this->assertSame(0, $this->bell($brandNew));
        $this->assertSame(0, $this->bell($complete));
        $mails = $this->mailsTo($noProfile);
        $this->assertCount(1, $mails);

        $digest = AdminNotification::find()->where(['title' => 'Incomplete Customer Profiles'])->one();
        $this->assertStringContainsString($noProfile->username, $digest->message);
        $this->assertStringContainsString($partial->username . ' (' . $partial->email . '): address', $digest->message);
        $this->assertStringNotContainsString($complete->username, $digest->message);

        // Run twice in a day: nothing is repeated.
        (new IncompleteProfileReminderService())->sendReminders();
        $this->assertCount(1, $this->mailsTo($noProfile));
        $this->assertSame(1, (int) AdminNotification::find()->where(['title' => 'Incomplete Customer Profiles'])->count());
    }

    public function testTheNextDaysReminderKeepsOneUnreadBellRow(): void
    {
        $customer = $this->customer(time() - 2 * 86400);
        (new IncompleteProfileReminderService())->sendReminders();
        CustomerNotification::updateAll(['created_at' => time() - 86400], ['user_id' => $customer->id]);

        (new IncompleteProfileReminderService())->sendReminders();

        $this->assertCount(2, $this->mailsTo($customer));
        $this->assertSame(1, $this->bell($customer));
    }

    public function testAnUnfinishedAccountWithNothingLiveIsDisabledAfterAMonth(): void
    {
        $old = strtotime('-1 month -1 day');
        $idle = $this->customer($old);
        $tooNew = $this->customer(strtotime('-20 days'));
        $complete = $this->customer($old);
        $this->profile($complete, 'Flat 1');
        $withService = $this->customer($old);
        $hosting = new \common\models\Hosting();
        $hosting->setAttributes(['user_id' => $withService->id, 'primary_domain' => 'live-' . bin2hex(random_bytes(4)) . '.test', 'start_date' => '2026-01-01', 'current_expiry_date' => '2027-11-30', 'status' => \common\models\Hosting::STATUS_SUSPENDED, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($hosting->save(false));
        $owing = $this->customer($old);
        $invoice = new \common\models\Invoice();
        $invoice->setAttributes(['user_id' => $owing->id, 'invoice_number' => 'TEST-IDLE-' . bin2hex(random_bytes(4)), 'client_name' => 'Idle', 'subtotal_amount' => 100, 'total_payable' => 100, 'status' => \common\models\Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($invoice->save(false));

        // First run only warns, however old the account: at least 7 days' notice.
        (new IncompleteProfileReminderService())->sendReminders();
        $this->assertNotNull(User::findOne($idle->id)->profile_warned_at);
        $this->assertNull(User::findOne($withService->id)->profile_warned_at);
        $this->assertSame(User::getStatusActive(), (int) User::findOne($idle->id)->status);
        $this->tester->grabSentEmails(); // (read below by recipient and subject)
        foreach ([$idle, $withService] as $customer) {
            User::updateAll(['profile_warned_at' => strtotime('-8 days')], ['id' => $customer->id]);
        }
        CustomerNotification::deleteAll(['user_id' => $idle->id]);

        $service = new IncompleteProfileReminderService();
        $service->sendReminders();

        $status = static fn(User $user): int => (int) User::findOne($user->id)->status;
        $this->assertSame(User::getStatusSuspended(), $status($idle));
        foreach ([$tooNew, $complete, $withService, $owing] as $kept) {
            $this->assertSame(User::getStatusActive(), $status($kept));
        }
        $this->assertGreaterThanOrEqual(1, $service->disabled);
        $this->assertNotSame(User::findOne($idle->id)->auth_key, $idle->auth_key, 'old remember-me cookies stop working');
        $this->assertNull(User::findOne($withService->id)->profile_warned_at, 'warning dropped while something is live');
        $this->assertCount(2, $this->mailsTo($idle));
        $this->assertSame('Your account has been disabled', $this->mailsTo($idle)[1]->getSubject());
        $this->assertStringContainsString($idle->username, AdminNotification::find()->where(['title' => 'Inactive Accounts Disabled'])->one()->message);
        $this->assertStringNotContainsString($owing->username, AdminNotification::find()->where(['title' => 'Inactive Accounts Disabled'])->one()->message);

        // Staff set it Active again: a month's grace, reminded meanwhile.
        $reactivated = User::findOne($idle->id);
        $reactivated->status = User::getStatusActive();
        $reactivated->updated_at = time(); // as the Activate button does
        $this->assertTrue($reactivated->save(false));
        (new IncompleteProfileReminderService())->sendReminders();
        $this->assertSame(User::getStatusActive(), $status($idle));
        $this->assertSame(1, $this->bell($idle));
    }

    public function testAFailedCronJobIsAlertedOnceADay(): void
    {
        AdminAlertService::cronJobFailed('Unit Job', 'first');
        AdminAlertService::cronJobFailed('Unit Job', 'second');
        AdminAlertService::cronJobFailed('Other Job', 'third');

        $this->assertSame(1, (int) AdminNotification::find()->where(['title' => 'Cron Job Failed: Unit Job'])->count());
        $this->assertSame(1, (int) AdminNotification::find()->where(['title' => 'Cron Job Failed: Other Job'])->count());
        $subjects = array_map(static fn($mail): string => (string) $mail->getSubject(), $this->tester->grabSentEmails());
        $this->assertSame(1, count(array_filter($subjects, static fn(string $s): bool => str_contains($s, 'Cron Job Failed: Unit Job'))));
    }

    public function testAJobThatThrowsEmailsAdminsAndExitsNonZero(): void
    {
        $controller = new \console\controllers\CronController('cron', Yii::$app);
        $run = new \ReflectionMethod($controller, 'runLocked');

        $code = $run->invoke($controller, 'miracle:unit-failing-job', 'Unit Failing Job', static function (): string {
            throw new \RuntimeException('it broke');
        });

        $this->assertSame(\yii\console\ExitCode::UNSPECIFIED_ERROR, $code);
        $this->assertStringContainsString('it broke', AdminNotification::find()->where(['title' => 'Cron Job Failed: Unit Failing Job'])->one()->message);
        $this->assertNotEmpty(array_filter($this->tester->grabSentEmails(), static fn($mail): bool => str_contains((string) $mail->getSubject(), 'Cron Job Failed: Unit Failing Job')));
    }

    public function testAJobIsOnUntilTurnedOff(): void
    {
        $this->assertTrue(CronJob::isEnabled('cron/razorpay-sync'));
        CronJob::set('cron/razorpay-sync', false, 'unit');
        $this->assertFalse(CronJob::isEnabled('cron/razorpay-sync'));
        CronJob::set('cron/razorpay-sync', true, 'unit');
        $this->assertTrue(CronJob::isEnabled('cron/razorpay-sync'));
    }

    private function customer(int $createdAt): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'cron-' . $suffix, 'email' => 'cron-' . $suffix . '@example.test', 'status' => User::getStatusActive()]);
        $user->password = 'CronTestPassword1';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $user->updateAttributes(['created_at' => $createdAt, 'updated_at' => $createdAt]);

        return $user;
    }

    private function profile(User $user, string $address): void
    {
        $profile = new CustomerBillingProfile();
        $profile->setAttributes(['user_id' => $user->id, 'legal_name' => 'Cron Test', 'address_line_1' => $address, 'city' => 'Mumbai', 'state' => 'Maharashtra', 'state_code' => '27', 'postal_code' => '400001', 'country' => 'India', 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($profile->save(false));
    }

    private function bell(User $user): int
    {
        return (int) CustomerNotification::find()->where(['user_id' => $user->id, 'title' => IncompleteProfileReminderService::TITLE])->count();
    }

    private function mailsTo(User $user): array
    {
        return array_values(array_filter($this->tester->grabSentEmails(), static fn($mail): bool => array_key_exists($user->email, (array) $mail->getTo())));
    }
}
