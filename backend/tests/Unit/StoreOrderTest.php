<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use common\models\BillingRun;
use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Product;
use common\models\ProductOrder;
use common\models\Transaction;
use common\models\User;
use common\services\billing\ManualInvoiceService;
use common\services\billing\OutstandingBalanceService;
use common\services\billing\PaymentProcessingService;
use common\services\payment\ExchangeRateService;
use common\services\StoreOrderService;
use Yii;
use yii\db\Transaction as DbTransaction;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

final class StoreOrderTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;
    private Product $_product;
    /** @var array<string, mixed> */
    private array $_billingParams = [];

    protected function _before(): void
    {
        parent::_before();
        $this->_billingParams = Yii::$app->params['billing'];
        Yii::$app->params['billing']['gst'] = ['cgst' => 9.00, 'sgst' => 9.00, 'igst' => 18.00];
        Yii::$app->params['billing']['supplierGstin'] = '07AAAAA0000A1Z5';
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User(['username' => 'buy-' . $suffix, 'email' => 'buy-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $this->_user->password = 'StoreTestPassword1';
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
        $this->_product = new Product(['name' => 'Shop Theme ' . $suffix, 'technology' => 'wordpress', 'kind' => 'theme', 'summary' => 'A shop theme.', 'description' => 'For shops.', 'version' => '1.2', 'price_single_inr' => 2999, 'price_single_usd' => 49, 'price_unlimited_inr' => 7999, 'price_unlimited_usd' => 129, 'status' => Product::STATUS_PUBLISHED]);
        $this->assertTrue($this->_product->save(), json_encode($this->_product->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction?->isActive) {
            $this->_dbTransaction->rollBack();
        }
        Yii::$app->params['billing'] = $this->_billingParams;
        parent::_after();
    }

    public function testABuyerInIndiaPaysInRupeesWithGstAndGetsAKeyOncePaid(): void
    {
        $this->profile('India', 'Delhi', '07');
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single');

        $this->assertNull($invoice->usd_total);
        $this->assertEquals(2999.00, (float) $invoice->subtotal_amount);
        $this->assertEquals(3538.82, (float) $invoice->total_payable, '2999 + 9% CGST + 9% SGST');
        $line = InvoiceItem::findOne(['invoice_id' => $invoice->id]);
        $this->assertStringContainsString('single site licence, version 1.2', $line->description);
        $this->assertSame($invoice->id, (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single')->id, 'a second click opens the same invoice');
        $this->assertNotSame($invoice->id, (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'unlimited')->id, 'another licence is another order');

        $order = ProductOrder::findOne(['invoice_id' => $invoice->id]);
        $this->assertFalse($order->isPaid());
        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $invoice->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertMatchesRegularExpression('/^[0-9A-F]{5}(-[0-9A-F]{5}){3}$/', (string) $order->licence_key);
        $this->assertNotSame($invoice->id, (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single')->id, 'bought again: a new order');

        // Refunded and closed: no more key or download.
        $payment->updateAttributes(['status' => Transaction::STATUS_REFUNDED]);
        (new PaymentProcessingService())->transactionChanged($payment);
        (new \common\services\billing\InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));
        $this->assertFalse(ProductOrder::findOne($order->id)->isPaid());
    }

    public function testABuyerAbroadPaysTheDollarPrice(): void
    {
        $this->profile('Canada', 'Ontario', null);
        $http = new MockTransport();
        $http->appendResponse(new Response(['content' => json_encode(['amount' => 1.0, 'base' => 'USD', 'date' => date('Y-m-d'), 'rates' => ['INR' => 88.5]]), 'headers' => ['http-code' => '200', 'content-type' => 'application/json']]));
        $invoice = (new StoreOrderService(new ManualInvoiceService(exchangeRates: new ExchangeRateService($http))))->buy((int) $this->_user->id, $this->_product, 'unlimited');

        $this->assertEquals(129.00, (float) $invoice->usd_total);
        $this->assertEquals(0.0, (float) $invoice->igst_percent + (float) $invoice->cgst_percent, 'no GST abroad');
    }

    public function testAnUnpaidOrderIsNeverAddedToARenewalInvoiceAndCanBeCancelled(): void
    {
        $this->profile('India', 'Delhi', '07');
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single');
        $invoice->updateAttributes(['due_date' => '2000-01-01', 'created_at' => strtotime('-30 days')]);
        $run = new BillingRun();
        $run->setAttributes(['run_number' => 'TEST-RUN-' . bin2hex(random_bytes(4)), 'status' => BillingRun::STATUS_RUNNING, 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($run->save(false));
        $next = new Invoice();
        $next->setAttributes(['user_id' => $this->_user->id, 'invoice_number' => 'TEST-ST-' . bin2hex(random_bytes(3)), 'client_name' => 'X', 'subtotal_amount' => 100, 'discount_amount' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'total_payable' => 100, 'status' => Invoice::STATUS_UNPAID, 'billing_run_id' => $run->id, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($next->save(false));
        $this->assertEqualsWithDelta(0.0, (new OutstandingBalanceService())->transferForUser($next), 0.001);

        // Long past its due date: the daily job cancels it, and leaves a fresh one alone.
        $fresh = (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'unlimited');
        $this->assertSame(1, (new StoreOrderService())->cancelAbandoned());
        $this->assertSame(Invoice::STATUS_CANCELLED, Invoice::findOne($invoice->id)->status);
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($fresh->id)->status);
    }

    public function testACouponWorksOnTheStoreUnlessLimitedToOtherServices(): void
    {
        $this->profile('India', 'Delhi', '07');
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single');
        $this->assertSame(InvoiceItem::TYPE_PRODUCT, (int) InvoiceItem::findOne(['invoice_id' => $invoice->id])->item_type);
        $coupons = new \common\services\billing\InvoiceCouponService();

        $this->coupon('HOSTONLY' . $this->_user->id, [(string) InvoiceItem::TYPE_HOSTING]);
        try {
            $coupons->apply($invoice, 'HOSTONLY' . $this->_user->id);
            $this->fail('a hosting-only coupon must not apply to a theme');
        } catch (\DomainException $e) {
            $this->assertSame('Coupon cannot be used for this service.', $e->getMessage());
        }
        $this->coupon('STORE' . $this->_user->id, [(string) InvoiceItem::TYPE_PRODUCT]);
        $this->assertEquals(299.90, (float) $coupons->apply($invoice, 'STORE' . $this->_user->id)->discount_amount, '10% of 2999');
        $coupons->remove(Invoice::findOne($invoice->id));
        $this->coupon('ALL' . $this->_user->id, []);
        $this->assertEquals(299.90, (float) $coupons->apply(Invoice::findOne($invoice->id), 'ALL' . $this->_user->id)->discount_amount, 'an unrestricted coupon applies too');
    }

    /** @param string[] $appliesTo */
    private function coupon(string $code, array $appliesTo): void
    {
        $coupon = new \common\models\Coupon();
        $coupon->setAttributes(['name' => $code, 'code' => $code, 'type' => \common\models\Coupon::TYPE_P, 'amount' => 10, 'is_active' => \common\models\Coupon::IS_ACTIVE_Y, 'uses_per_coupon' => 0, 'applies_to' => $appliesTo]);
        $this->assertTrue($coupon->save(), json_encode($coupon->errors));
    }

    private function profile(string $country, string $state, ?string $stateCode): void
    {
        $profile = new CustomerBillingProfile();
        $profile->setAttributes(['user_id' => $this->_user->id, 'legal_name' => 'Buyer Ltd', 'address_line_1' => '1 Main Rd', 'city' => 'City', 'postal_code' => '110001', 'country' => $country, 'state' => $state, 'state_code' => $stateCode, 'is_gst_applicable' => 0, 'default_discount_amount' => 0, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($profile->save(false));
    }
}
