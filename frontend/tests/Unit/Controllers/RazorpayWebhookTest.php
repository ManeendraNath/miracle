<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Controllers;

use Codeception\Test\Unit;
use common\fixtures\UserFixture;
use common\models\AdminNotification;
use common\models\Invoice;
use common\models\Transaction;
use common\models\User;
use frontend\controllers\WebhookController;
use frontend\tests\Support\UnitTester;
use Yii;
use yii\web\BadRequestHttpException;

/** Razorpay payment.captured webhook: signed, idempotent, safe on mismatches. */
final class RazorpayWebhookTest extends Unit
{
    private const SECRET = 'webhook-test-secret';

    protected UnitTester $tester;
    private Invoice $_invoice;
    private Transaction $_order;

    public function _before(): void
    {
        $this->tester->haveFixtures(['user' => ['class' => UserFixture::class, 'dataFile' => codecept_data_dir() . 'user.php']]);
        Yii::$app->params['razorpay'] = ['enabled' => true, 'keyId' => 'rzp_test_unit', 'keySecret' => 'unit-key-secret', 'webhookSecret' => self::SECRET];

        $suffix = bin2hex(random_bytes(4));
        $this->_invoice = new Invoice();
        $this->_invoice->setAttributes(['user_id' => (int) User::find()->min('id'), 'invoice_number' => 'TEST-WH-' . $suffix, 'client_name' => 'Webhook Test', 'subtotal_amount' => 1000, 'discount_amount' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'total_payable' => 1000, 'status' => Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($this->_invoice->save(false));
        $this->_order = new Transaction();
        $this->_order->setAttributes(['invoice_id' => $this->_invoice->id, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => 'order_wh_' . $suffix, 'amount' => 1000, 'status' => Transaction::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($this->_order->save(false));
    }

    public function _after(): void
    {
        Transaction::deleteAll(['invoice_id' => $this->_invoice->id]);
        Invoice::deleteAll(['id' => $this->_invoice->id]);
    }

    public function testCapturedPaymentSettlesTheInvoiceOnceEvenIfDeliveredTwice(): void
    {
        $body = $this->event('payment.captured', 'pay_wh_ok', 100000);

        $this->assertSame(['status' => 'success'], $this->deliver($body));
        $this->assertSame(['status' => 'success'], $this->deliver($body), 'Razorpay retries: same answer, no double record');

        $order = Transaction::findOne($this->_order->id);
        $this->assertSame([Transaction::STATUS_CAPTURED, 'pay_wh_ok'], [$order->status, $order->gateway_payment_id]);
        $this->assertEqualsWithDelta(23.60, (float) $order->gateway_fee, 0.001, "Razorpay's fee from the webhook is stored");
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($this->_invoice->id)->status);
        $this->assertSame(1, (int) Transaction::find()->where(['invoice_id' => $this->_invoice->id, 'status' => Transaction::STATUS_CAPTURED])->count());
    }

    public function testForgedSignatureIsRejected(): void
    {
        $this->expectException(BadRequestHttpException::class);
        try {
            $this->deliver($this->event('payment.captured', 'pay_wh_forged', 100000), 'forged-signature');
        } finally {
            $this->assertSame(Transaction::STATUS_PENDING, Transaction::findOne($this->_order->id)->status);
        }
    }

    public function testAPaymentWithNoOrderIsIgnored(): void
    {
        $body = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'pay_no_order', 'order_id' => null, 'amount' => 100, 'currency' => 'INR']]]]);

        $this->assertSame(['status' => 'ignored'], $this->deliver($body));
    }

    public function testOtherEventsAreIgnored(): void
    {
        $this->assertSame(['status' => 'ignored'], $this->deliver($this->event('payment.authorized', 'pay_wh_auth', 100000)));
        $this->assertSame(Transaction::STATUS_PENDING, Transaction::findOne($this->_order->id)->status);
    }

    public function testAmountMismatchIsNotRecordedAndAlertsAnAdmin(): void
    {
        $this->assertSame(['status' => 'not_recorded'], $this->deliver($this->event('payment.captured', 'pay_wh_short', 50000)));
        $this->assertSame(Transaction::STATUS_PENDING, Transaction::findOne($this->_order->id)->status);
        $this->assertTrue(AdminNotification::find()->where(['like', 'message', 'pay_wh_short'])->exists());
        AdminNotification::deleteAll(['like', 'message', 'pay_wh_short']);
    }

    public function testCheckoutCallbackRecordsAPaymentOnlyWithAValidSignature(): void
    {
        $user = User::findOne((int) $this->_invoice->user_id);
        Yii::$app->user->login($user);
        $controller = new \frontend\controllers\InvoiceController('invoice', Yii::$app);
        Yii::$app->controller = $controller; // redirect(['view']) resolves against it
        $post = static fn(array $data) => Yii::$app->request->setBodyParams($data);
        $orderId = (string) $this->_order->gateway_order_id;

        $post(['razorpay_order_id' => $orderId, 'razorpay_payment_id' => 'pay_cb_forged', 'razorpay_signature' => 'forged']);
        $controller->actionRazorpayCallback($this->_invoice->invoice_number);
        $this->assertSame(Transaction::STATUS_PENDING, Transaction::findOne($this->_order->id)->status);
        $this->assertStringContainsString('could not verify', (string) Yii::$app->session->getFlash('error'));

        // Signature Razorpay Checkout returns: HMAC-SHA256(order_id|payment_id, key secret).
        $post(['razorpay_order_id' => $orderId, 'razorpay_payment_id' => 'pay_cb_ok', 'razorpay_signature' => hash_hmac('sha256', $orderId . '|pay_cb_ok', 'unit-key-secret')]);
        $controller->actionRazorpayCallback($this->_invoice->invoice_number);
        $this->assertSame(Transaction::STATUS_CAPTURED, Transaction::findOne($this->_order->id)->status);
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($this->_invoice->id)->status);
        $this->assertSame('Payment received. Thank you!', Yii::$app->session->getFlash('success'));
        Yii::$app->user->logout();
    }

    private function event(string $event, string $paymentId, int $amountMinor): string
    {
        return json_encode(['event' => $event, 'payload' => ['payment' => ['entity' => ['id' => $paymentId, 'order_id' => $this->_order->gateway_order_id, 'amount' => $amountMinor, 'fee' => 2360, 'currency' => 'INR', 'status' => 'captured']]]], JSON_THROW_ON_ERROR);
    }

    private function deliver(string $body, ?string $signature = null): array
    {
        Yii::$app->request->setRawBody($body);
        Yii::$app->request->headers->set('X-Razorpay-Signature', $signature ?? hash_hmac('sha256', $body, self::SECRET));

        return (new WebhookController('webhook', Yii::$app))->actionRazorpay();
    }
}
