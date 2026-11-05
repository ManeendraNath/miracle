<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\Invoice;
use common\models\Transaction;
use common\models\User;
use common\services\payment\RazorpayService;
use Yii;
use yii\db\Transaction as DbTransaction;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

/** Missed-payment sync, fee capture and API refunds, against canned Razorpay answers. */
final class RazorpaySyncTest extends Unit
{
    private const CONFIG = ['enabled' => true, 'keyId' => 'rzp_test_unit', 'keySecret' => 'unit-key-secret', 'webhookSecret' => 'unit-webhook-secret'];

    private ?DbTransaction $_dbTransaction = null;
    private Invoice $_invoice;
    private MockTransport $_http;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'sync-' . $suffix, 'email' => 'sync-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'SyncTestPassword';
        $this->assertTrue($user->save(), json_encode($user->errors));
        $this->_invoice = new Invoice();
        $this->_invoice->setAttributes(['user_id' => $user->id, 'invoice_number' => 'TEST-SYNC-' . bin2hex(random_bytes(4)), 'client_name' => 'Sync Test', 'subtotal_amount' => 1000, 'discount_amount' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'total_payable' => 1000, 'status' => Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($this->_invoice->save(false));
        $this->_http = new MockTransport();
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testSyncRecordsAPaymentWhoseCallbackAndWebhookWereLost(): void
    {
        $order = $this->transaction(Transaction::STATUS_PENDING, 'order_sync_' . bin2hex(random_bytes(4)), time() - 3600);
        $this->reply(['items' => [
            ['id' => 'pay_sync_failed', 'status' => 'failed', 'amount' => 100000],
            ['id' => 'pay_sync_ok', 'status' => 'captured', 'amount' => 100000, 'fee' => 2360],
        ]]);

        $result = $this->service()->syncPending();

        $this->assertGreaterThanOrEqual(1, $result['captured']);
        $order->refresh();
        $this->assertSame([Transaction::STATUS_CAPTURED, 'pay_sync_ok'], [$order->status, $order->gateway_payment_id]);
        $this->assertEqualsWithDelta(23.60, (float) $order->gateway_fee, 0.001);
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($this->_invoice->id)->status);
        $this->assertStringEndsWith('orders/' . $order->gateway_order_id . '/payments', $this->_http->flushRequests()[0]->getFullUrl());
    }

    public function testSyncFillsInTheFeeOfAPaymentRecordedFromTheBrowser(): void
    {
        $payment = $this->transaction(Transaction::STATUS_CAPTURED, 'order_fee_' . bin2hex(random_bytes(4)), time(), 'pay_fee_' . bin2hex(random_bytes(4)));
        $this->reply(['id' => $payment->gateway_payment_id, 'status' => 'captured', 'amount' => 100000, 'fee' => 2360]);

        $this->service()->syncPending();

        $this->assertEqualsWithDelta(23.60, (float) Transaction::findOne($payment->id)->gateway_fee, 0.001);
    }

    public function testSyncAlertsOnceAboutARefundMadeInTheDashboard(): void
    {
        $payment = $this->transaction(Transaction::STATUS_CAPTURED, 'order_dash_' . bin2hex(random_bytes(4)), time(), 'pay_dash_' . bin2hex(random_bytes(4)));
        $payment->updateAttributes(['gateway_fee' => 23.60]);
        $alerts = static fn(): int => (int) AdminNotification::find()->where(['title' => 'Razorpay Refund Not Recorded'])->andWhere(['like', 'message', $payment->gateway_payment_id])->count();

        $this->reply(['id' => $payment->gateway_payment_id, 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 40000]);
        $this->assertSame(1, $this->service()->syncPending()['unrecordedRefunds']);
        $this->assertSame(1, $alerts());

        $this->reply(['id' => $payment->gateway_payment_id, 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 40000]);
        $this->service()->syncPending();
        $this->assertSame(1, $alerts(), 'not repeated every 30 minutes');
    }

    public function testADashboardRefundBlocksApplyingThePayment(): void
    {
        $payment = $this->transaction(Transaction::STATUS_CAPTURED, 'order_apply_' . bin2hex(random_bytes(4)), time(), 'pay_apply_' . bin2hex(random_bytes(4)));
        $this->reply(['id' => $payment->gateway_payment_id, 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 0]);
        $this->service()->assertRefundsRecorded($payment);

        $this->reply(['id' => $payment->gateway_payment_id, 'status' => 'refunded', 'amount' => 100000, 'amount_refunded' => 100000]);
        $this->expectExceptionMessage('Record it with Refund first');
        $this->service()->assertRefundsRecorded($payment);
    }

    public function testRefundGoesThroughRazorpayUnlessTheDashboardAlreadyDidIt(): void
    {
        $this->reply(['id' => 'pay_r', 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 0]);
        $this->reply(['id' => 'rfnd_unit_1', 'amount' => 100000]);
        $this->assertSame('rfnd_unit_1', $this->service()->refundPayment('pay_r', 100000));
        $refund = $this->_http->flushRequests()[1];
        $this->assertSame(['POST', 100000], [$refund->getMethod(), $refund->getData()['amount']]);
        $this->assertStringEndsWith('payments/pay_r/refund', $refund->getFullUrl());

        $this->reply(['id' => 'pay_r', 'status' => 'refunded', 'amount' => 100000, 'amount_refunded' => 100000]);
        $this->assertNull($this->service()->refundPayment('pay_r', 100000), 'already refunded: only record it');
        $this->assertCount(1, $this->_http->flushRequests(), 'no second refund is sent');

        // Partial refund of ₹400 made in the dashboard: recording that amount works, any other amount is refused.
        $this->reply(['id' => 'pay_r', 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 40000]);
        $this->assertNull($this->service()->refundPayment('pay_r', 40000));
        $this->reply(['id' => 'pay_r', 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 40000]);
        $this->reply(['id' => 'rfnd_unit_2', 'amount' => 60000]);
        $this->assertSame('rfnd_unit_2', $this->service()->refundPayment('pay_r', 60000, 40000), 'the rest, once the ₹400 is recorded');
        $this->_http->flushRequests();

        $this->reply(['id' => 'pay_r', 'status' => 'captured', 'amount' => 100000, 'amount_refunded' => 40000]);
        $this->expectExceptionMessage('Enter ₹400.00 as the refund amount');
        $this->service()->refundPayment('pay_r', 100000);
    }

    private function service(): RazorpayService
    {
        return new RazorpayService(self::CONFIG, $this->_http);
    }

    private function reply(array $data, int $status = 200): void
    {
        $this->_http->appendResponse(new Response(['content' => json_encode($data, JSON_THROW_ON_ERROR), 'headers' => ['http-code' => (string) $status, 'content-type' => 'application/json']]));
    }

    private function transaction(string $status, string $orderId, int $createdAt, ?string $paymentId = null): Transaction
    {
        $transaction = new Transaction();
        $transaction->setAttributes(['invoice_id' => $this->_invoice->id, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $orderId, 'gateway_payment_id' => $paymentId, 'amount' => 1000, 'gateway_fee' => 0, 'status' => $status, 'paid_at' => $status === Transaction::STATUS_CAPTURED ? time() : null, 'created_at' => $createdAt, 'updated_at' => $createdAt], false);
        $this->assertTrue($transaction->save(false));

        return $transaction;
    }
}
