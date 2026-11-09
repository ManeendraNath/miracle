<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\Domains;
use common\models\Hosting;
use common\models\User;
use common\services\billing\ServiceRenewalReminderService;
use DateTimeImmutable;
use Yii;
use yii\db\Transaction as DbTransaction;

/** Staff get one expiry email a day, with every service due and its price. */
final class ExpiryDigestTest extends Unit
{
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
    }

    public function testOneEmailListsEveryServiceDueWithItsPrice(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'digest-' . $suffix, 'email' => 'digest-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'DigestTestPassword1';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $today = new DateTimeImmutable('2030-03-01');
        $expiry = $today->modify('+30 days')->format('Y-m-d');
        $created = strtotime('2029-01-01');

        $domain = new Domains();
        $domain->setAttributes(['user_id' => $user->id, 'domain_url' => 'digest-' . $suffix . '.com', 'registrar' => 'Test', 'registered_date' => '2029-01-01', 'current_expiry_date' => $expiry, 'status' => Domains::STATUS_ACTIVE, 'renewal_base_amount' => 1200, 'created_at' => $created, 'updated_at' => $created], false);
        $this->assertTrue($domain->save(false));
        $hosting = new Hosting();
        $hosting->setAttributes(['user_id' => $user->id, 'primary_domain' => 'digest-' . $suffix . '.com', 'current_expiry_date' => $expiry, 'status' => Hosting::STATUS_ACTIVE, 'created_at' => $created, 'updated_at' => $created], false);
        $this->assertTrue($hosting->save(false));

        $before = (int) AdminNotification::find()->max('id');
        (new ServiceRenewalReminderService())->sendReminders($today);
        $alerts = AdminNotification::find()->where(['>', 'id', $before])->andWhere(['type' => 'renewal'])->all();

        $this->assertCount(1, $alerts, 'one email for both services');
        $this->assertStringContainsString('Domain digest-' . $suffix . '.com: expires', $alerts[0]->message);
        $this->assertStringContainsString('Renewal about ₹1,200.00', $alerts[0]->message);
        $this->assertStringContainsString('Hosting digest-' . $suffix . '.com', $alerts[0]->message);
        $this->assertStringContainsString('No renewal price is set', $alerts[0]->message);

        (new ServiceRenewalReminderService())->sendReminders($today);
        $this->assertSame(1, (int) AdminNotification::find()->where(['>', 'id', $before])->andWhere(['type' => 'renewal'])->count(), 'not repeated');
    }
}
