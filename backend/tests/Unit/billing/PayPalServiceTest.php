<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\components\CustomYii;
use common\models\Invoice;
use common\models\Transaction;
use common\models\User;
use common\services\payment\ExchangeRateService;
use common\services\payment\PayPalService;
use Yii;
use yii\db\Transaction as DbTransaction;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

/** PayPal orders in US dollars for a rupee invoice, against canned PayPal answers. */
final class PayPalServiceTest extends Unit
{
    private const CONFIG = ['clientId' => 'unit-client', 'secret' => 'unit-secret', 'sandbox' => true];

    private ?DbTransaction $_dbTransaction = null;
    private Invoice $_invoice;
    private MockTransport $_http;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'paypal-' . $suffix, 'email' => 'paypal-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'PayPalTestPassword1';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $this->_invoice = new Invoice();
        $this->_invoice->setAttributes(['user_id' => $user->id, 'invoice_number' => 'TEST-PP-' . $suffix, 'client_name' => 'Abroad Ltd', 'client_country' => 'United States', 'subtotal_amount' => 3500, 'discount_amount' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'total_payable' => 3500, 'status' => Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($this->_invoice->save(false));
        $this->_http = new MockTransport();
        $exists = (new \yii\db\Query())->from('{{%site_setting}}')->where(['id' => 1])->exists();
        $exists
            ? Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_inr_rate' => 85, 'usd_rate_auto' => 0, 'paypal_abroad' => 1], ['id' => 1])->execute()
            : Yii::$app->db->createCommand()->insert('{{%site_setting}}', ['id' => 1, 'site_name' => 'Test', 'site_url' => 'https://example.test', 'mobile_1' => 9999999999, 'email_1' => 'test@example.test', 'usd_inr_rate' => 85, 'usd_rate_auto' => 0, 'paypal_abroad' => 1, 'updated_at' => time()])->execute();
        $this->resetSettings();
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        $this->resetSettings();
        parent::_after();
    }

    public function testAnApprovedOrderIsCapturedInDollarsAndSettlesTheRupeeInvoice(): void
    {
        $this->reply(['access_token' => 'tok']);
        $this->reply(['id' => 'PPORDER1', 'status' => 'CREATED'], 201);
        $order = $this->service()->createOrder($this->_invoice, 3500);
        $this->assertSame([Transaction::GATEWAY_PAYPAL, 'PPORDER1', 3500.0], [(int) $order->gateway_id, $order->gateway_order_id, (float) $order->amount]);
        $sent = $this->_http->flushRequests();
        $this->assertSame(['currency_code' => 'USD', 'value' => '41.18'], $sent[1]->getData()['purchase_units'][0]['amount'], '₹3,500 at ₹85 = US$41.18');

        $this->reply(['access_token' => 'tok']);
        $this->reply($this->completed('41.18', '2.20'), 201);
        $captured = $this->service()->captureOrder('PPORDER1');

        $this->assertSame([Transaction::STATUS_CAPTURED, 'CAPTURE1'], [$captured->status, $captured->gateway_payment_id]);
        $this->assertEqualsWithDelta(187.00, (float) $captured->gateway_fee, 0.001, 'US$2.20 fee at ₹85');
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($this->_invoice->id)->status);
        $this->assertSame('capture-PPORDER1', $this->_http->flushRequests()[1]->getHeaders()->get('PayPal-Request-Id'), 'a retried capture never charges twice');
    }

    public function testADifferentAmountIsNotRecorded(): void
    {
        $this->pendingOrder('PPORDER2');
        $this->expectException(\DomainException::class);
        $this->service()->record('PPORDER2', $this->completed('1.00', '0.30'));
    }

    public function testAPaymentPayPalHasNotCompletedStaysPending(): void
    {
        $order = $this->pendingOrder('PPORDER3');
        $held = $this->completed('41.18', '2.20');
        $held['purchase_units'][0]['payments']['captures'][0]['status'] = 'PENDING';
        $this->service()->record('PPORDER3', $held);
        $order->refresh();
        $this->assertSame([Transaction::STATUS_PENDING, 'CAPTURE1'], [$order->status, $order->gateway_payment_id]);
        // Shown as a payment under review: no second PayPal order, no proof upload.
        $this->assertTrue((new \common\services\billing\InvoicePaymentService())->hasPendingProof((int) $this->_invoice->id));

        $this->service()->record('PPORDER3', $this->completed('41.18', '2.20'));
        $this->assertSame(Transaction::STATUS_CAPTURED, Transaction::findOne($order->id)->status, 'recorded once PayPal completes it');
    }

    public function testNothingIsTakenWhenTheInvoiceNoLongerOwesTheOrderAmount(): void
    {
        $order = $this->pendingOrder('PPORDER5');
        $this->_invoice->updateAttributes(['total_payable' => 3000]); // e.g. a coupon applied in another tab
        $result = $this->service()->captureOrder('PPORDER5');

        $this->assertSame(Transaction::STATUS_FAILED, $result->status);
        $this->assertStringContainsString('amount due changed', (string) $result->user_payment_notes);
        $this->assertSame([], $this->_http->flushRequests(), 'PayPal was never asked to capture');
        $this->assertSame(Transaction::STATUS_FAILED, Transaction::findOne($order->id)->status);
    }

    public function testRecoveringBeforeANewOrderRecordsAPaymentWhoseBrowserWasLost(): void
    {
        $this->pendingOrder('PPORDER4');
        $this->reply(['access_token' => 'tok']);
        $this->reply($this->completed('41.18', '2.20'));
        $paypal = $this->service();
        $this->assertSame(1, $paypal->recoverPaidOrders((int) $this->_invoice->id));
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($this->_invoice->id)->status);
    }

    public function testNoButtonWhenSwitchedOffOrWithoutARate(): void
    {
        $this->assertTrue($this->service()->acceptsCustomerPayments());
        $this->assertFalse((new PayPalService(['clientId' => '', 'secret' => ''], $this->_http))->acceptsCustomerPayments(), 'no keys');
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['paypal_abroad' => 0], ['id' => 1])->execute();
        $this->resetSettings();
        $this->assertFalse($this->service()->acceptsCustomerPayments(), 'switched off');
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['paypal_abroad' => 1, 'usd_inr_rate' => null], ['id' => 1])->execute();
        $this->resetSettings();
        $this->assertFalse($this->service()->acceptsCustomerPayments(), 'no rate');
    }

    public function testTheDailyRateLessTheMarginIsUsedAndFetchedAtMostTwiceADay(): void
    {
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_rate_auto' => 1, 'usd_rate_margin' => 3, 'usd_rate_checked_at' => null], ['id' => 1])->execute();
        $this->resetSettings();
        $this->reply(['amount' => 1.0, 'base' => 'USD', 'date' => '2026-11-06', 'rates' => ['INR' => 93.92]]);
        $rates = new ExchangeRateService($this->_http);

        $this->assertSame(91.1024, $rates->usdRate(), '93.92 less 3%');
        $this->assertSame(91.1024, $rates->usdRate(), 'no second request within 12 hours');
        $this->assertCount(1, $this->_http->flushRequests());

        // A reply far from the last good rate is refused; the last good rate stays.
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_rate_checked_at' => null], ['id' => 1])->execute();
        $this->resetSettings();
        $this->reply(['rates' => ['INR' => 9.39]]);
        $this->assertSame(91.1024, $rates->usdRate());

        // Not updated for over 4 days: the rate by hand is used.
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_rate_checked_at' => time(), 'usd_rate_fetched_at' => strtotime('-5 days')], ['id' => 1])->execute();
        $this->resetSettings();
        $this->assertSame(85.0, $rates->usdRate());

        // The API is down and no rate was ever fetched: the rate by hand is used.
        Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_market_rate' => null, 'usd_rate_checked_at' => null], ['id' => 1])->execute();
        $this->resetSettings();
        $this->reply(['message' => 'down'], 503);
        $this->assertSame(85.0, $rates->usdRate());
    }

    private function pendingOrder(string $orderId): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAttributes(['invoice_id' => $this->_invoice->id, 'gateway_id' => Transaction::GATEWAY_PAYPAL, 'gateway_order_id' => $orderId, 'amount' => 3500, 'raw_payload' => json_encode(['currency' => 'USD', 'usd' => '41.18', 'rate' => 85]), 'status' => Transaction::STATUS_PENDING, 'created_at' => time() - 60, 'updated_at' => time() - 60], false);
        $this->assertTrue($transaction->save(false));

        return $transaction;
    }

    private function completed(string $usd, string $fee): array
    {
        return ['id' => 'PPORDER', 'status' => 'COMPLETED', 'purchase_units' => [['payments' => ['captures' => [[
            'id' => 'CAPTURE1', 'status' => 'COMPLETED', 'amount' => ['currency_code' => 'USD', 'value' => $usd],
            'seller_receivable_breakdown' => ['paypal_fee' => ['currency_code' => 'USD', 'value' => $fee]], 'create_time' => gmdate('Y-m-d\TH:i:s\Z'),
        ]]]]]];
    }

    private function service(): PayPalService
    {
        return new PayPalService(self::CONFIG, $this->_http);
    }

    private function reply(array $data, int $status = 200): void
    {
        $this->_http->appendResponse(new Response(['content' => json_encode($data, JSON_THROW_ON_ERROR), 'headers' => ['http-code' => (string) $status, 'content-type' => 'application/json']]));
    }

    private function resetSettings(): void
    {
        (new \ReflectionProperty(CustomYii::class, '_settings'))->setValue(null, null);
    }
}
