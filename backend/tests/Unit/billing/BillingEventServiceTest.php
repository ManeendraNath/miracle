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

    public function testPriceFollowsTheCycleAndRisesOncePerYear(): void
    {
        $prices = new \common\services\billing\RenewalPriceCalculator();
        $itemId = random_int(100000, 999999);
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'price-' . $suffix, 'email' => 'price-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'PriceTestPassword';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $fulfil = function (string $start, string $end, float $amount) use ($itemId, $user): void {
            $event = new \common\models\BillingEvent();
            $event->setAttributes(['billing_run_id' => $this->billingRun(), 'user_id' => (int) $user->id, 'item_type' => InvoiceItem::TYPE_HOSTING, 'item_id' => $itemId, 'description' => 'Hosting', 'period_start' => $start, 'period_end' => $end, 'previous_amount' => 0, 'increase_percent' => 0, 'calculated_amount' => $amount, 'amount' => $amount, 'is_manual_amount' => 0, 'due_date' => $start, 'status' => \common\models\BillingEvent::STATUS_FULFILLED, 'created_at' => time(), 'updated_at' => time()], false);
            $this->assertTrue($event->save(false));
        };

        $fulfil('2025-01-01', '2025-12-31', 6000.00);
        $this->assertEquals(6600.00, $prices->calculate(InvoiceItem::TYPE_HOSTING, $itemId, 6000.00, 10.0, 12)->amount, 'yearly: +10%');
        $this->assertEquals(600.00, $prices->calculate(InvoiceItem::TYPE_HOSTING, $itemId, 600.00, 10.0, 1)->amount, 'switched to monthly: the base for the new cycle, not ₹6000');

        $fulfil('2026-01-01', '2026-01-31', 600.00);
        $this->assertEquals(600.00, $prices->calculate(InvoiceItem::TYPE_HOSTING, $itemId, 600.00, 10.0, 1)->amount, 'monthly: no rise after one month');
    }

    public function testAnchorDayFromTheServiceIsUsedForThePeriod(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User();
        $user->username = 'anchor-' . $suffix;
        $user->email = 'anchor-' . $suffix . '@example.test';
        $user->password = 'AnchorTestPassword1';
        $user->status = User::getStatusActive();
        $user->created_at = $user->updated_at = time();
        $this->assertTrue($user->save(), json_encode($user->errors));

        // Expiry was clamped to 28 Feb by an earlier renewal; the service renews on the 30th.
        $event = (new BillingEventService())->create($this->billingRun(), new BillableRenewal((int) $user->id, InvoiceItem::TYPE_HOSTING, 987655, 'Hosting', '2026-02-28', 1000.00, 1, 0.0, 30));

        $this->assertSame(['2026-03-01', '2026-03-30'], [$event->period_start, $event->period_end]);
    }

    private function billingRun(): int
    {
        $run = new BillingRun();
        $run->setAttributes(['run_number' => 'TEST-EVT-' . bin2hex(random_bytes(4)), 'status' => BillingRun::STATUS_RUNNING, 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($run->save(false));
        return (int) $run->id;
    }
}
