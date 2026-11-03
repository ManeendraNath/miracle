<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\BillingEvent;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use common\models\User;
use common\services\billing\AutoInvoiceService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\PaymentProcessingService;
use common\services\payment\RazorpayService;
use Yii;
use yii\db\Transaction as DbTransaction;

final class PaymentStatusTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User(['username' => 'pay-' . $suffix, 'email' => 'pay-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $this->_user->password = 'PaymentTestPassword';
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testRefundLeavesTheInvoiceDueUnlessTheAdminClosesIt(): void
    {
        $invoice = $this->invoice(1000.00);
        $payment = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        (new PaymentProcessingService())->transactionChanged($payment);
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);

        $payment->status = Transaction::STATUS_REFUNDED;
        $this->assertTrue($payment->save(false));
        (new PaymentProcessingService())->transactionChanged($payment);
        // E.g. a wrong payment was returned: the customer still has to pay.
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($invoice->id)->status);

        // "Refund & Close Invoice": nothing more is owed, and later reconciles leave it closed.
        (new InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));
        (new PaymentProcessingService())->transactionChanged($payment);
        $this->assertSame(Invoice::STATUS_REFUNDED, Invoice::findOne($invoice->id)->status);
    }

    public function testInvoiceWithAKeptPaymentCannotBeClosedAsRefunded(): void
    {
        $invoice = $this->invoice(1000.00);
        $first = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 500.00);
        $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 500.00);
        $first->status = Transaction::STATUS_REFUNDED;
        $this->assertTrue($first->save(false));
        (new PaymentProcessingService())->transactionChanged($first);
        $this->assertSame(Invoice::STATUS_PARTIALLY_PAID, Invoice::findOne($invoice->id)->status, 'the other ₹500 is kept, so ₹500 is still due');

        $this->expectException(\DomainException::class);
        (new InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));
    }

    public function testOnlinePaymentOnAnAlreadyPaidInvoiceIsFlaggedForAnAdmin(): void
    {
        $invoice = $this->invoice(1000.00);
        $order = $this->payment($invoice, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_PENDING, 'order_test_' . bin2hex(random_bytes(4)));
        // Paid meanwhile by an approved UPI proof.
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED));
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);

        $captured = (new RazorpayService([]))->capture($order->gateway_order_id, 'pay_test_late', 100000);

        $this->assertSame(Transaction::STATUS_CAPTURED, $captured->status, 'the money was taken, so it is recorded');
        $this->assertTrue($captured->isUnapplied(), 'the checkout callback warns the customer from this');
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);
    }

    public function testASettledInvoiceRefusesAnotherPayment(): void
    {
        $invoice = $this->invoice(1000.00);
        (new InvoicePaymentService())->assertPayable($invoice);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_CAPTURED));

        $this->expectException(\DomainException::class);
        (new InvoicePaymentService())->assertPayable(Invoice::findOne($invoice->id));
    }

    public function testClosingAsRefundedOwesNothingAndReleasesTheBilledPeriod(): void
    {
        $older = $this->invoice(400.00);
        $olderEvent = $this->billingEvent($older);
        $invoice = $this->invoice(1000.00);
        $event = $this->billingEvent($invoice);
        // $older was carried forward into $invoice.
        $arrears = new InvoiceItem();
        $arrears->setAttributes(['invoice_id' => $invoice->id, 'item_type' => InvoiceItem::TYPE_ARREARS, 'source_invoice_id' => $older->id, 'category' => 'Previous Outstanding', 'description' => 'Arrears', 'quantity' => 1, 'unit_price' => 400, 'total_price' => 400], false);
        $this->assertTrue($arrears->save(false));
        $older->status = Invoice::STATUS_CARRIED_FORWARD;
        $this->assertTrue($older->save(false));

        $proof = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        try {
            (new InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));
            $this->fail('a pending proof must block closing');
        } catch (\DomainException) {
        }
        $proof->status = Transaction::STATUS_FAILED;
        $this->assertTrue($proof->save(false));

        (new InvoicePaymentService())->closeAsRefunded(Invoice::findOne($invoice->id));

        $this->assertSame(0.0, (new InvoicePaymentService())->getOutstanding(Invoice::findOne($invoice->id)));
        $this->assertSame(BillingEvent::STATUS_CANCELLED, BillingEvent::findOne($event->id)->status, 'the period is billed again next run');
        $this->assertSame(BillingEvent::STATUS_CANCELLED, BillingEvent::findOne($olderEvent->id)->status, 'and so is the carried-forward one');
    }

    public function testOnlinePaymentThatOverpaysAPartlyPaidInvoiceAlertsAnAdmin(): void
    {
        $invoice = $this->invoice(1000.00);
        $order = $this->payment($invoice, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_PENDING, 'order_test_' . bin2hex(random_bytes(4)));
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 400.00));
        $paymentId = 'pay_test_' . bin2hex(random_bytes(4));

        (new RazorpayService([]))->capture($order->gateway_order_id, $paymentId, 100000);

        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);
        $this->assertTrue(AdminNotification::find()->where(['like', 'message', $paymentId])->andWhere(['like', 'message', 'Overpaid by: ₹400'])->exists());
    }

    public function testGatewayTestOrdersAreRefusedWithLiveKeys(): void
    {
        $live = new RazorpayService(['enabled' => true, 'keyId' => 'rzp_live_example', 'keySecret' => 'secret']);
        $this->assertFalse($live->isTestMode());
        $this->expectException(\DomainException::class);
        $live->createTestOrder(100); // refused before any API call: live keys would take real money
    }

    private function billingEvent(Invoice $invoice): BillingEvent
    {
        $event = new BillingEvent();
        $event->setAttributes(['user_id' => $this->_user->id, 'item_type' => InvoiceItem::TYPE_HOSTING, 'item_id' => random_int(100000, 999999), 'description' => 'Renewal', 'period_start' => '2026-12-01', 'period_end' => '2027-11-30', 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => '2026-12-01', 'invoice_id' => $invoice->id, 'status' => BillingEvent::STATUS_INVOICED, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($event->save(false));

        return $event;
    }

    private function invoice(float $amount): Invoice
    {
        $invoice = new Invoice();
        $invoice->setAttributes(['user_id' => $this->_user->id, 'invoice_number' => 'TEST-PAY-' . bin2hex(random_bytes(4)), 'client_name' => 'Payment Test', 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'discount_amount' => 0, 'status' => Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($invoice->save(), json_encode($invoice->errors));
        $item = new InvoiceItem();
        $item->setAttributes(['invoice_id' => $invoice->id, 'item_type' => InvoiceItem::TYPE_HOSTING, 'category' => 'Test', 'description' => 'Line', 'quantity' => 1, 'unit_price' => $amount, 'total_price' => $amount], false);
        $this->assertTrue($item->save(), json_encode($item->errors));
        (new AutoInvoiceService())->calculateInvoiceTotals($invoice);

        return $invoice;
    }

    private function payment(Invoice $invoice, int $gateway, string $status, ?string $orderId = null, ?float $amount = null): Transaction
    {
        $payment = new Transaction();
        $payment->setAttributes(['invoice_id' => $invoice->id, 'gateway_id' => $gateway, 'gateway_order_id' => $orderId, 'amount' => $amount ?? (float) $invoice->total_payable, 'status' => $status, 'paid_at' => $status === Transaction::STATUS_CAPTURED ? time() : null, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));

        return $payment;
    }
}
