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
    protected \backend\tests\Support\UnitTester $tester;
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
        $refunded = ProductOrder::findOne($order->id);
        $this->assertFalse($refunded->isPaid());
        $this->assertSame('refunded', $refunded->state(), 'shown as refunded, never as awaiting payment');
        $this->expectException(\DomainException::class);
        (new StoreOrderService())->cancel($refunded);
    }

    public function testAPaidServiceOpensAJobTicketWithTheBrief(): void
    {
        $this->profile('India', 'Delhi', '07');
        $service = new Product(['name' => 'Speed Up ' . $this->_user->id, 'technology' => 'wordpress', 'kind' => Product::KIND_SERVICE, 'summary' => 'Faster site.', 'description' => 'We make it fast.', 'delivery_days' => 3, 'price_single_inr' => 4999, 'price_single_usd' => 79, 'price_unlimited_inr' => 9999, 'price_unlimited_usd' => 150, 'status' => Product::STATUS_PUBLISHED]);
        $this->assertTrue($service->save(), json_encode($service->errors));
        $this->assertNull($service->price_unlimited_inr, 'a service has one price');
        $this->assertNull($service->price('unlimited', false));
        $service->refresh();
        $this->assertTrue($service->validate(), 'a saved product (4999.00 from the database) saves again: ' . json_encode($service->errors));

        try {
            (new StoreOrderService())->buy((int) $this->_user->id, $service, 'single', 'too short');
            $this->fail('a service needs a brief');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('20 to 5,000', $e->getMessage());
        }
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $service, 'single', 'My shop takes 9 seconds to load on phones.', 'example.com');
        $this->assertSame(InvoiceItem::TYPE_SERVICE_ORDER, (int) InvoiceItem::findOne(['invoice_id' => $invoice->id])->item_type);
        $again = (new StoreOrderService())->buy((int) $this->_user->id, $service, 'single', 'Also the checkout page is slow, please look at it.', '');
        $this->assertSame($invoice->id, $again->id, 'the open order is reused, with the latest brief');
        $order = ProductOrder::findOne(['invoice_id' => $invoice->id]);
        $this->assertSame('Also the checkout page is slow, please look at it.', $order->brief);

        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $invoice->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertNull($order->licence_key, 'no licence key for a service');
        $ticket = $order->supportTicket;
        $this->assertNotNull($ticket);
        $this->assertSame((int) $this->_user->id, (int) $ticket->user_id);
        $this->assertSame(\common\models\SupportTicket::STATUS_OPEN, $ticket->status);
        $this->assertStringContainsString('checkout page is slow', $ticket->message);
        $this->assertSame('Fixed price', $order->licenceLabel());
        $this->assertSame(0, (int) StoreOrderService::paidBuyers($service)->count(), 'no update emails for a service');

        $service->kind = 'theme';
        $this->assertFalse($service->validate(['kind']), 'the type is fixed once there are orders');
        $service->kind = Product::KIND_SERVICE;

        try {
            (new StoreOrderService())->buy((int) $this->_user->id, $service, 'single', 'A long address that will not fit.', str_repeat('a', 246) . '.com');
            $this->fail('the scheme counts towards the length');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('website address', $e->getMessage());
        }
    }

        public function testAPaidCarePlanStartsAMonthlyMaintenance(): void
    {
        $this->profile('India', 'Delhi', '07');
        $plan = new Product(['name' => 'Care ' . $this->_user->id, 'technology' => 'wordpress', 'kind' => Product::KIND_PLAN, 'summary' => 'Monthly care.', 'description' => 'We look after it.', 'price_single_inr' => 1999, 'price_single_usd' => 59, 'status' => Product::STATUS_PUBLISHED]);
        $this->assertTrue($plan->save(), json_encode($plan->errors));
        $this->assertSame('plans', $plan->section());
        $this->assertFalse($plan->hasLicence());

        $settings = \common\models\BillingSettings::forType(InvoiceItem::TYPE_MAINTENANCE);
        $settings->is_active = 0;
        $this->assertTrue($settings->save(false));
        try {
            (new StoreOrderService())->buy((int) $this->_user->id, $plan, 'single', '', 'shop.example');
            $this->fail('no plan is sold while renewals cannot run');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('contact us', $e->getMessage());
        }
        $settings->is_active = 1;
        $settings->invoice_before_days = 30;
        $this->assertTrue($settings->save(false));
        \common\models\CronJob::updateAll(['is_enabled' => 1], ['job' => 'cron/run-billing']);

        try {
            (new StoreOrderService())->buy((int) $this->_user->id, $plan, 'single');
            $this->fail('a plan needs the website');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('website', $e->getMessage());
        }
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $plan, 'single', '', 'shop.example');
        $line = InvoiceItem::findOne(['invoice_id' => $invoice->id]);
        $this->assertSame(InvoiceItem::TYPE_MAINTENANCE, (int) $line->item_type, 'maintenance coupons apply to the first month');

        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $invoice->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);

        $order = ProductOrder::findOne(['invoice_id' => $invoice->id]);
        $this->assertTrue($order->isPaid());
        $this->assertSame('Monthly', $order->licenceLabel());
        $this->assertNull($order->licence_key);
        $this->assertNotNull($order->supportTicket);
        $care = $order->maintenance;
        $this->assertNotNull($care);
        $this->assertSame('https://shop.example', $care->website_url);
        $this->assertSame(1, (int) $care->renewal_cycle_months);
        $this->assertSame(1999.0, (float) $care->renewal_base_amount);
        $this->assertSame(date('Y-m-d'), $care->start_date);
        $this->assertSame(1, \common\services\billing\RenewalPeriodCalculator::months($care->start_date, $care->current_expiry_date), 'the first month is paid');

        // The next month is invoiced a week ahead at most, whatever the global lead time.
        $this->assertSame(date('Y-m-d', (int) strtotime($care->current_expiry_date . ' -7 days')), (new \common\services\billing\InvoicePaymentService())->renewalState($care, InvoiceItem::TYPE_MAINTENANCE)['issuedFrom']);
        $expiry = new \DateTimeImmutable($care->current_expiry_date);
        $ids = static fn(\DateTimeImmutable $today): array => array_map(static fn($r): int => $r->itemId, (new \common\services\billing\collector\MaintenanceBillingCollector())->collect($today));
        $this->assertNotContains((int) $care->id, $ids($expiry->modify('-8 days')));
        $this->assertContains((int) $care->id, $ids($expiry->modify('-7 days')));

        // Refunding the first month ends the plan.
        $payment->updateAttributes(['refunded_amount' => (float) $payment->amount, 'status' => Transaction::STATUS_REFUNDED]);
        (new \common\services\billing\InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));
        $care->refresh();
        $this->assertSame(\common\models\Maintenance::STATUS_CANCELLED, (int) $care->status);
    }

    public function testARenewalAbroadChargesAboutThePlanDollarPrice(): void
    {
        $inr = \common\services\payment\PayPalService::inrCharging(59.0, 90.0);
        $this->assertEqualsWithDelta(59.0, (float) \common\services\payment\PayPalService::chargeFor($inr, 90.0), 0.02);
    }

        public function testASoftwareSubscriptionLicenceWorksWhilePaidOnItsSite(): void
    {
        $this->profile('India', 'Delhi', '07');
        \common\models\BillingSettings::updateAll(['is_active' => 1], ['service_type' => InvoiceItem::TYPE_MAINTENANCE]);
        \common\models\CronJob::updateAll(['is_enabled' => 1], ['job' => 'cron/run-billing']);
        $software = new Product(['name' => 'Portal ' . $this->_user->id, 'technology' => 'yii2', 'kind' => Product::KIND_SOFTWARE, 'summary' => 'Billing.', 'description' => 'Billing portal.', 'price_single_inr' => 1499, 'price_single_usd' => 29, 'status' => Product::STATUS_PUBLISHED]);
        $this->assertFalse($software->save(), 'the yearly price is required');
        $software->price_unlimited_inr = 14999;
        $software->price_unlimited_usd = 290;
        $this->assertTrue($software->save(), json_encode($software->errors));
        $this->assertSame('Yearly', $software->licenceName('unlimited'));

        try {
            (new StoreOrderService())->buy((int) $this->_user->id, $software, 'unlimited');
            $this->fail('software needs the address it is installed at');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('install', $e->getMessage());
        }
        $invoice = (new StoreOrderService())->buy((int) $this->_user->id, $software, 'unlimited', '', 'https://Billing.Example.com/');
        $this->assertEquals(14999.0, (float) InvoiceItem::findOne(['invoice_id' => $invoice->id])->unit_price);
        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $invoice->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);

        $order = ProductOrder::findOne(['invoice_id' => $invoice->id]);
        $this->assertNotNull($order->licence_key);
        $this->assertSame('billing.example.com', $order->licence_site);
        $this->assertSame(12, (int) $order->maintenance->renewal_cycle_months, 'yearly');
        $this->assertEquals(14999.0, (float) $order->maintenance->renewal_base_amount, 'renews at the yearly price');
        $this->assertEquals(0.0, (float) $order->maintenance->renewal_increase_percent, 'no price rise in the terms');
        $this->assertSame(12, \common\services\billing\RenewalPeriodCalculator::months($order->maintenance->start_date, $order->maintenance->current_expiry_date));
        $this->assertSame('Yearly', $order->licenceLabel());

        $status = static fn(string $site): string => \common\services\LicenceService::status(strtolower((string) $order->licence_key), $site)['status'];
        $this->assertSame('active', $status('https://www.billing.example.com'));
        $this->assertSame('wrong_site', $status('https://other.example.com'));
        $this->assertSame('unknown', \common\services\LicenceService::status('NOPE', 'billing.example.com')['status']);
        $this->assertNotNull(ProductOrder::findOne($order->id)->licence_checked_at);

        // Past its paid period and the grace days: the key stops working (the copy itself carries on).
        $order->maintenance->updateAttributes(['current_expiry_date' => date('Y-m-d', strtotime('-' . (ProductOrder::LICENCE_GRACE_DAYS + 1) . ' days'))]);
        $this->assertSame('expired', $status('billing.example.com'));

        // Cancelled but paid up: works to the end of the period.
        $order->maintenance->updateAttributes(['current_expiry_date' => date('Y-m-d', strtotime('+20 days')), 'status' => \common\models\Maintenance::STATUS_CANCELLED]);
        $this->assertSame('active', $status('billing.example.com'));
        $order->maintenance->updateAttributes(['status' => \common\models\Maintenance::STATUS_ACTIVE]);

        // The renewal invoice is named after the product.
        $order->maintenance->updateAttributes(['current_expiry_date' => date('Y-m-d', strtotime('+5 days'))]);
        $renewals = (new \common\services\billing\collector\MaintenanceBillingCollector())->collect(new \DateTimeImmutable('today'));
        $mine = array_values(array_filter($renewals, static fn($r): bool => $r->itemId === (int) $order->maintenance_id));
        $this->assertStringStartsWith($software->name . ' renewal - ', $mine[0]->description);
    }

    public function testAnInstalledCopyKeepsTheLastAnswerWhenTheServerIsDown(): void
    {
        $params = Yii::$app->params;
        $file = (string) Yii::getAlias('@common/runtime/licence.json');
        $saved = is_file($file) ? file_get_contents($file) : null;
        Yii::$app->params['licence'] = ['key' => 'ABC', 'server' => 'https://licence.example.test'];
        Yii::$app->params['frontendBaseUrl'] = 'https://billing.example.com';
        try {
            $http = new MockTransport();
            $http->appendResponse(new Response(['content' => json_encode(['status' => 'expired', 'until' => '2026-10-01', 'product' => 'Portal']), 'headers' => ['http-code' => '200', 'content-type' => 'application/json']]));
            \common\services\LicenceService::check($http);
            $this->assertSame('expired', \common\services\LicenceService::current()['status']);
            $this->assertSame('POST', $http->flushRequests()[0]->getMethod(), 'the key is not put in the URL');
            $down = new MockTransport();
            $down->appendResponse(new Response(['content' => 'Bad gateway', 'headers' => ['http-code' => '502']]));
            \common\services\LicenceService::check($down);
            $this->assertSame('expired', \common\services\LicenceService::current()['status'], 'an outage changes nothing');
            $this->assertArrayHasKey('failing_since', \common\services\LicenceService::current(), 'the failure is noted for the banner');
        } finally {
            Yii::$app->params = $params;
            $saved !== null ? file_put_contents($file, $saved) : @unlink($file);
        }
    }

    public function testSupportEndsOnTheLastDayOfAShortMonth(): void
    {
        $at = static fn(string $date): int => (new \DateTimeImmutable($date . ' 10:00', new \DateTimeZone(Yii::$app->timeZone)))->getTimestamp();
        $order = new ProductOrder(['paid_at' => $at('2026-08-31')]);
        $this->assertSame($at('2027-02-28'), $order->supportUntil());
        $order->paid_at = $at('2026-11-19');
        $this->assertSame($at('2027-05-19'), $order->supportUntil());
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

    public function testANewVersionIsEmailedToPaidBuyersOnly(): void
    {
        $this->profile('India', 'Delhi', '07');
        $paid = (new StoreOrderService())->buy((int) $this->_user->id, $this->_product, 'single');
        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $paid->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $paid->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);
        // Bought too, but never paid: no email.
        $other = new User(['username' => 'unpaid-' . $this->_user->id, 'email' => 'unpaid-' . $this->_user->id . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $other->password = 'StoreTestPassword1';
        $this->assertTrue($other->save());
        $profile = new CustomerBillingProfile();
        $profile->setAttributes(['user_id' => $other->id, 'legal_name' => 'Other', 'address_line_1' => '1 Rd', 'city' => 'Delhi', 'postal_code' => '110001', 'country' => 'India', 'state' => 'Delhi', 'state_code' => '07', 'is_gst_applicable' => 0, 'default_discount_amount' => 0, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($profile->save(false));
        (new StoreOrderService())->buy((int) $other->id, $this->_product, 'single');

        $order = ProductOrder::findOne(['invoice_id' => $paid->id]);
        $this->assertSame(strtotime('+6 months', (int) $order->paid_at), $order->supportUntil());
        $this->_product->updateAttributes(['version' => '1.3', 'changelog' => "1.3\n- Dark mode"]);
        $this->assertSame([1, 0], (new StoreOrderService())->notifyUpdate($this->_product));
        $emails = $this->tester->grabSentEmails();
        $toBuyer = array_values(array_filter($emails, fn($m): bool => array_key_exists($this->_user->email, (array) $m->getTo())));
        $this->assertCount(1, $toBuyer, 'the paid buyer, once; the unpaid one gets nothing');
        $this->assertCount(0, array_filter($emails, static fn($m): bool => array_key_exists($other->email, (array) $m->getTo())));
        $copies = array_filter($emails, static fn($m): bool => str_starts_with((string) $m->getSubject(), '[Copy]'));
        $this->assertLessThanOrEqual(1, count($copies), 'one copy for staff, when a billing address is set');
        $text = quoted_printable_decode((string) $toBuyer[0]->toString());
        $this->assertStringContainsString('version 1.3', $text);
        $this->assertStringContainsString('Dark mode', $text);
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
