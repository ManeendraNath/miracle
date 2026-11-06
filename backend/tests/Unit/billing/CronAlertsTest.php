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
        $user->updateAttributes(['created_at' => $createdAt]);

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
