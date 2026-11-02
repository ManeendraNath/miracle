<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\Coupon;
use common\models\CouponRedemption;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use common\models\User;
use common\services\billing\AutoInvoiceService;
use common\services\billing\InvoiceCouponService;
use common\services\payment\RazorpayService;
use Yii;
use yii\db\Transaction as DbTransaction;

final class InvoiceCouponServiceTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User();
        $this->_user->username = 'coupon-test-' . $suffix;
        $this->_user->email = 'coupon-' . $suffix . '@example.test';
        $this->_user->password = 'CouponTestPassword';
        $this->_user->status = User::getStatusActive();
        $this->_user->created_at = $this->_user->updated_at = time();
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testRestrictedCouponDiscountsOnlyMatchingLinesAndRecomputesTax(): void
    {
        $this->coupon('HOST10', ['applies_to' => [(string) InvoiceItem::TYPE_HOSTING]]);
        $invoice = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00, InvoiceItem::TYPE_CUSTOM => 500.00]);
        $this->assertEquals(1770.00, (float) $invoice->total_payable);

        $invoice = (new InvoiceCouponService())->apply($invoice, ' host10 ');

        // 10% of the hosting line only; taxable 1400 + 18% GST.
        $this->assertSame('HOST10', $invoice->coupon_code);
        $this->assertEquals(100.00, (float) $invoice->discount_amount);
        $this->assertEquals(1652.00, (float) Invoice::findOne($invoice->id)->total_payable);
        $redemption = CouponRedemption::findOne(['invoice_id' => $invoice->id]);
        $this->assertNotNull($redemption);
        $this->assertEquals(100.00, (float) $redemption->discount_amount);
    }

    public function testArrearsLineIsNeverDiscountedOrTaxed(): void
    {
        $this->coupon('ALL10', []);
        $invoice = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00, InvoiceItem::TYPE_ARREARS => 2000.00]);

        $invoice = (new InvoiceCouponService())->apply($invoice, 'ALL10');

        // 10% of 1000 (not of 3000); (1000 - 100) * 1.18 + 2000 arrears.
        $this->assertEquals(100.00, (float) $invoice->discount_amount);
        $this->assertEquals(3062.00, (float) Invoice::findOne($invoice->id)->total_payable);
    }

    public function testUsageLimitIsRespected(): void
    {
        $this->coupon('ONCE', ['uses_per_coupon' => 1]);
        $service = new InvoiceCouponService();
        $service->apply($this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00]), 'ONCE');

        $second = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00]);
        try {
            $service->apply($second, 'ONCE');
            $this->fail('Second use should be rejected.');
        } catch (\DomainException $e) {
            $this->assertSame('Coupon usage limit has been reached.', $e->getMessage());
        }
        $this->assertNull(Invoice::findOne($second->id)->coupon_code);
        $this->assertEquals(1180.00, (float) Invoice::findOne($second->id)->total_payable);
    }

    public function testBlockedOnceSomethingIsReceivedOrPending(): void
    {
        $this->coupon('ALL10', []);
        $service = new InvoiceCouponService();
        $cases = [
            'captured' => [Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED],
            'proof pending' => [Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING],
        ];
        foreach ($cases as $label => [$gateway, $status]) {
            $invoice = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00]);
            $payment = new Transaction();
            $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => $gateway, 'amount' => 100.00, 'status' => $status, 'created_at' => time(), 'updated_at' => time()], false);
            $this->assertTrue($payment->save(false));
            $this->assertNotNull($service->blockReason($invoice), $label);
            try {
                $service->apply($invoice, 'ALL10');
                $this->fail("Coupon should be blocked: {$label}");
            } catch (\DomainException $e) {
                $this->assertStringContainsString('payment has been made or started', $e->getMessage(), $label);
            }
        }

        // An admin's manual (non-coupon) discount is not stacked with a customer coupon.
        $discounted = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00], 50.00);
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already has a discount');
        $service->apply($discounted, 'ALL10');
    }

    public function testUnpaidOnlineOrderIsSupersededAndStillCapturedIfPaidLater(): void
    {
        $this->coupon('ALL10', []);
        $invoice = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00]);
        $order = new Transaction();
        $order->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => 'order_test_' . bin2hex(random_bytes(4)), 'amount' => 1180.00, 'status' => Transaction::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($order->save(false));
        $service = new InvoiceCouponService();
        $this->assertNull($service->blockReason($invoice));

        $service->apply($invoice, 'ALL10');

        $order->refresh();
        $this->assertSame(Transaction::STATUS_FAILED, $order->status);
        $this->assertSame(RazorpayService::SUPERSEDED_NOTE, $order->user_payment_notes);
        $this->assertEquals(1062.00, (float) Invoice::findOne($invoice->id)->total_payable);

        // The old checkout was still open and the customer paid it: the money is recorded.
        (new RazorpayService([]))->capture($order->gateway_order_id, 'pay_test_late', 118000);

        $this->assertSame(Transaction::STATUS_CAPTURED, Transaction::findOne($order->id)->status);
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);
        // ...and once paid, the coupon can no longer change.
        $this->assertNotNull($service->blockReason(Invoice::findOne($invoice->id)));
    }

    public function testRemoveRestoresOriginalTotal(): void
    {
        $this->coupon('FLAT200', ['type' => Coupon::TYPE_F, 'amount' => 200]);
        $service = new InvoiceCouponService();
        $invoice = $this->invoice([InvoiceItem::TYPE_HOSTING => 1000.00, InvoiceItem::TYPE_CUSTOM => 500.00]);
        $applied = $service->apply($invoice, 'FLAT200');
        $this->assertEquals(1534.00, (float) $applied->total_payable);

        $removed = $service->remove($applied);

        $this->assertNull($removed->coupon_code);
        $this->assertNull($removed->coupon_id);
        $this->assertEquals(0.00, (float) $removed->discount_amount);
        $this->assertEquals(1770.00, (float) Invoice::findOne($invoice->id)->total_payable);
        $this->assertFalse(CouponRedemption::find()->where(['invoice_id' => $invoice->id])->exists());
    }

    /** @param array<int, float> $lines item_type => line total */
    private function invoice(array $lines, float $discount = 0.00): Invoice
    {
        $invoice = new Invoice();
        $invoice->setAttributes([
            'user_id' => $this->_user->id,
            'invoice_number' => 'TEST-CP-' . bin2hex(random_bytes(4)),
            'client_name' => 'Coupon Test',
            'cgst_percent' => 9.00,
            'sgst_percent' => 9.00,
            'igst_percent' => 0.00,
            'discount_amount' => $discount,
            'status' => Invoice::STATUS_UNPAID,
            'created_at' => time(),
            'updated_at' => time(),
        ], false);
        $this->assertTrue($invoice->save(), json_encode($invoice->errors));
        foreach ($lines as $type => $amount) {
            $item = new InvoiceItem();
            $item->setAttributes(['invoice_id' => $invoice->id, 'item_type' => $type, 'category' => 'Test', 'description' => 'Line', 'quantity' => 1, 'unit_price' => $amount, 'total_price' => $amount], false);
            $this->assertTrue($item->save(), json_encode($item->errors));
        }
        (new AutoInvoiceService())->calculateInvoiceTotals($invoice);
        return $invoice;
    }

    private function coupon(string $code, array $attributes): Coupon
    {
        $coupon = new Coupon();
        $coupon->setAttributes($attributes + ['name' => $code, 'code' => $code, 'type' => Coupon::TYPE_P, 'amount' => 10, 'is_active' => Coupon::IS_ACTIVE_Y, 'uses_per_coupon' => 0]);
        $this->assertTrue($coupon->save(), json_encode($coupon->errors));
        return $coupon;
    }
}
