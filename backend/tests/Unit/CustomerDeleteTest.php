<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\models\Domains;
use common\models\Hosting;
use common\models\Maintenance;
use common\models\User;
use Yii;
use yii\db\Transaction;

/** A customer with an active service can't be deleted: they would keep the service without being billed. */
final class CustomerDeleteTest extends Unit
{
    private ?Transaction $_transaction = null;

    protected function _before(): void
    {
        $this->_transaction = Yii::$app->db->beginTransaction();
    }

    protected function _after(): void
    {
        $this->_transaction?->rollBack();
    }

    public function testActiveServicesBlockDeletingTheCustomer(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'del-' . $suffix, 'email' => 'del-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'DeleteTestPassword1';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $domain = new Domains(['user_id' => $user->id, 'domain_url' => 'del-' . $suffix . '.test', 'registrar' => 'x', 'registered_date' => '2020-01-01', 'current_expiry_date' => '2026-12-01', 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $this->assertTrue($domain->save(false));

        $user->status = User::getStatusDeleted();
        $this->assertFalse($user->validate(['status']));
        $this->assertStringContainsString('1 domain', $user->getFirstError('status'));

        $domain->updateAttributes(['status' => 0]);
        $this->assertTrue($user->validate(['status']), (string) $user->getFirstError('status'));

        // Suspended hosting and an overdue plan still owe a renewal: they block it too.
        $hosting = new Hosting(['user_id' => $user->id, 'primary_domain' => 'del-' . $suffix . '.test', 'current_expiry_date' => '2026-12-01', 'status' => Hosting::STATUS_SUSPENDED, 'created_at' => time(), 'updated_at' => time()]);
        $this->assertTrue($hosting->save(false));
        $this->assertFalse($user->validate(['status']));
        $this->assertStringContainsString('1 hosting', $user->getFirstError('status'));
        $hosting->updateAttributes(['status' => 0]);
        $plan = new Maintenance(['user_id' => $user->id, 'website_url' => 'https://del-' . $suffix . '.test', 'start_date' => '2026-01-01', 'current_expiry_date' => '2026-12-01', 'status' => Maintenance::STATUS_OVERDUE, 'created_at' => time(), 'updated_at' => time()]);
        $this->assertTrue($plan->save(false));
        $this->assertFalse($user->validate(['status']));
        $this->assertStringContainsString('1 maintenance plan', $user->getFirstError('status'));
    }

    public function testADeletedCustomerCannotGetARunningService(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'gone-' . $suffix, 'email' => 'gone-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'DeleteTestPassword1';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $user->updateAttributes(['status' => User::getStatusDeleted()]);
        $domain = new Domains(['user_id' => $user->id, 'domain_url' => 'gone-' . $suffix . '.test', 'registrar' => 'x', 'registered_date' => '2020-01-01', 'current_expiry_date' => '2026-12-01', 'status' => 1]);
        $this->assertFalse($domain->validate(['status']));
        $domain->status = 0;
        $this->assertTrue($domain->validate(['status']));
    }
}
