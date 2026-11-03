<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\BillingRun;
use common\models\BillingSettings;
use common\models\InvoiceItem;
use common\models\User;
use common\services\billing\BillingEventService;
use common\services\billing\dto\BillableRenewal;
use Yii;
use yii\db\Transaction as DbTransaction;

final class BillingEventServiceTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        BillingSettings::forType(InvoiceItem::TYPE_HOSTING)->updateAttributes(['is_active' => 1]);
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testPendingEventMovedToANewRunIsRepricedUnlessTheAmountWasSetByHand(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User();
        $user->username = 'event-' . $suffix;
        $user->email = 'event-' . $suffix . '@example.test';
        $user->password = 'EventTestPassword';
        $user->status = User::getStatusActive();
        $user->created_at = $user->updated_at = time();
        $this->assertTrue($user->save(), json_encode($user->errors));
        $service = new BillingEventService();
        $renewal = static fn(float $base): BillableRenewal => new BillableRenewal((int) $user->id, InvoiceItem::TYPE_HOSTING, 987654, 'Hosting', '2026-11-30', $base, 12, 0.0);

        $event = $service->create($this->billingRun(), $renewal(1000.00));
        $this->assertEquals(1000.00, (float) $event->amount);

        // The first run failed to invoice it; the admin then corrected the price.
        $event = $service->create($this->billingRun(), $renewal(1500.00));
        $this->assertEquals(1500.00, (float) $event->amount);

        $event->updateAttributes(['is_manual_amount' => 1, 'amount' => 1200.00]);
        $event = $service->create($this->billingRun(), $renewal(1800.00));
        $this->assertEquals(1200.00, (float) $event->amount);
    }

    private function billingRun(): int
    {
        $run = new BillingRun();
        $run->setAttributes(['run_number' => 'TEST-EVT-' . bin2hex(random_bytes(4)), 'status' => BillingRun::STATUS_RUNNING, 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($run->save(false));
        return (int) $run->id;
    }
}
