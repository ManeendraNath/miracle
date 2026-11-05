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

    public function testPendingEventMovedToANewRunFollowsThePriceAndTheOneTimeOverride(): void
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

        // An Admin's one-time "next renewal price" wins over the calculated one...
        $override = new BillableRenewal((int) $user->id, InvoiceItem::TYPE_HOSTING, 987654, 'Hosting', '2026-11-30', 1800.00, 12, 0.0, null, 999.00);
        $event = $service->create($this->billingRun(), $override);
        $this->assertSame([999.00, 1], [(float) $event->amount, (int) $event->is_manual_amount]);

        // ...and once the Admin clears it before the renewal is invoiced, the calculated price is back.
        $event = $service->create($this->billingRun(), $renewal(1800.00));
        $this->assertSame([1800.00, 0], [(float) $event->amount, (int) $event->is_manual_amount]);
    }

    public function testAPeriodOverlappingARenewalAwaitingPaymentIsNotBilledAgain(): void
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'overlap-' . $suffix, 'email' => 'overlap-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'OverlapTestPassword';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $service = new BillingEventService();
        $itemId = random_int(100000, 999999);
        $renewal = static fn(string $expiry): BillableRenewal => new BillableRenewal((int) $user->id, InvoiceItem::TYPE_HOSTING, $itemId, 'Hosting', $expiry, 1000.00, 12, 0.0);

        $first = $service->create($this->billingRun(), $renewal('2026-12-31'));
        $first->status = \common\models\BillingEvent::STATUS_INVOICED;
        $this->assertTrue($first->save(false));

        // Staff moved the expiry after its balance was carried forward.
        $this->assertSame((int) $first->id, (int) $service->create($this->billingRun(), $renewal('2027-01-31'))->id);
        $this->assertSame((int) $first->id, (int) $service->create($this->billingRun(), $renewal('2026-11-30'))->id);

        // Past the end of the billed period there is no overlap.
        $this->assertNotSame((int) $first->id, (int) $service->create($this->billingRun(), $renewal('2027-12-31'))->id);
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

        // The yearly renewal before the switch doesn't count: the rise comes after 12 monthly renewals.
        for ($month = 2; $month <= 11; $month++) {
            $start = sprintf('2026-%02d-01', $month);
            $fulfil($start, date('Y-m-t', strtotime($start)), 600.00);
        }
        $this->assertEquals(600.00, $prices->calculate(InvoiceItem::TYPE_HOSTING, $itemId, 600.00, 10.0, 1)->amount, 'after 11 months');
        $fulfil('2026-12-01', '2026-12-31', 600.00);
        $this->assertEquals(660.00, $prices->calculate(InvoiceItem::TYPE_HOSTING, $itemId, 600.00, 10.0, 1)->amount, 'the 13th monthly renewal');
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
