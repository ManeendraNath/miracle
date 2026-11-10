<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\components\CustomYii;
use common\models\BillingEvent;
use common\models\BillingRun;
use common\models\CustomerBillingProfile;
use common\models\CustomerNotification;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\services\billing\AutoInvoiceService;
use common\services\billing\BillingEventService;
use common\services\billing\InvoiceReviewService;
use Yii;
use yii\db\Transaction as DbTransaction;

/** With the review step on, a new renewal invoice waits, unseen by the customer, until staff send it. */
final class InvoiceReviewTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User(['username' => 'review-' . $suffix, 'email' => 'review-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $this->_user->password = 'ReviewTestPassword1';
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
        $this->setReview(true);
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        $this->resetSettingsCache();
    }

    public function testDraftIsHiddenUntilSentAndTheDueDateRestarts(): void
    {
        $invoice = $this->renewalInvoice();
        $this->assertSame(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertNull($invoice->emailed_at);
        $this->assertFalse(Invoice::forCustomer()->andWhere(['id' => $invoice->id])->exists(), 'not in the portal');
        $this->assertFalse(CustomerNotification::find()->where(['user_id' => $this->_user->id])->exists(), 'customer not told yet');

        $this->assertTrue((new InvoiceReviewService())->release(Invoice::findOne($invoice->id)));
        $sent = Invoice::findOne($invoice->id);
        $this->assertSame(Invoice::STATUS_UNPAID, $sent->status);
        $this->assertSame($invoice->due_date, $sent->due_date, 'still due on the expiry date');
        $this->assertNotNull($sent->emailed_at);
        $this->assertTrue(Invoice::forCustomer()->andWhere(['id' => $invoice->id])->exists());
        $this->assertTrue(CustomerNotification::find()->where(['user_id' => $this->_user->id])->exists());

        $this->expectException(\DomainException::class);
        (new InvoiceReviewService())->release($sent);
    }

    public function testARenewalIsDueOnTheExpiryDateAndALateOneSaysHowLongAgo(): void
    {
        $this->assertSame('2026-11-30', BillingEventService::expiryDate('2026-12-01'));

        $invoice = $this->renewalInvoice();
        $mailer = Yii::$app->mailer;
        $this->assertInstanceOf(\yii\mail\BaseMailer::class, $mailer);
        Invoice::updateAll(['due_date' => date('Y-m-d', strtotime('-3 days'))], ['id' => $invoice->id]);
        $late = Invoice::findOne($invoice->id);
        $this->assertSame(3, $late->renewalDaysExpired());
        $html = $mailer->render('invoice-html', ['invoice' => $late, 'outstanding' => (float) $late->total_payable]);
        $this->assertStringContainsString('Your service has expired.', $html);
        $this->assertStringContainsString('3 days ago', $html);
        $this->assertStringContainsString('3 day(s) ago', $mailer->render('invoice-text', ['invoice' => $late, 'outstanding' => (float) $late->total_payable]));

        Invoice::updateAll(['due_date' => date('Y-m-d', strtotime('+20 days'))], ['id' => $invoice->id]);
        $this->assertStringContainsString('Pay by (expiry date)', $mailer->render('invoice-html', ['invoice' => Invoice::findOne($invoice->id), 'outstanding' => 0.0]));
    }

    public function testAHeldInvoiceIsNumberedWhenSentAndCannotBePaidBefore(): void
    {
        $invoice = $this->renewalInvoice();
        $this->assertStringStartsWith('TMP-', $invoice->invoice_number, 'no number used while held');
        $this->assertSame('#' . $invoice->id . ' (waiting for review)', $invoice->reference(), 'staff never see the TMP- number');
        // Money that reaches a held invoice anyway (a crafted staff payment, a capture) is kept off it for staff to apply.
        $order = new \common\models\Transaction();
        $order->setAttributes(['invoice_id' => $invoice->id, 'user_id' => $invoice->user_id, 'gateway_id' => \common\models\Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => 'order_held', 'amount' => (float) $invoice->total_payable, 'status' => \common\models\Transaction::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($order->save(false));
        $captured = (new \common\services\payment\RazorpayService())->capture('order_held', 'pay_held');
        $this->assertSame([\common\models\Transaction::STATUS_CAPTURED, 1], [$captured->status, (int) $captured->is_unapplied]);
        $this->assertSame(Invoice::STATUS_DRAFT, Invoice::findOne($invoice->id)->status);
        $captured->delete();
        try {
            (new \common\services\billing\InvoicePaymentService())->assertPayable($invoice);
            $this->fail('A payment must not be applied to an invoice waiting for review.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('waiting for review', $e->getMessage());
        }

        (new InvoiceReviewService())->release(Invoice::findOne($invoice->id));
        $sent = Invoice::findOne($invoice->id);
        $this->assertStringStartsWith('MWT' . \common\services\billing\InvoiceNumberService::financialYear(time()) . '-', $sent->invoice_number, 'numbered in the financial year it is sent');
    }

    public function testAHeldInvoiceCancelledBeforeSendingIsNeverShownToTheCustomer(): void
    {
        $invoice = $this->renewalInvoice();
        (new \common\services\billing\InvoiceCancellationService())->cancel($invoice, 'Not needed.');
        $this->assertSame(Invoice::STATUS_CANCELLED, Invoice::findOne($invoice->id)->status);
        $this->assertFalse(Invoice::forCustomer()->andWhere(['id' => $invoice->id])->exists());
    }

    public function testSendingDatesTheInvoiceToday(): void
    {
        $invoice = $this->renewalInvoice();
        Invoice::updateAll(['created_at' => strtotime('-10 days')], ['id' => $invoice->id]);
        (new InvoiceReviewService())->release(Invoice::findOne($invoice->id));
        $this->assertSame(date('Y-m-d'), date('Y-m-d', (int) Invoice::findOne($invoice->id)->created_at));
    }

    public function testAnOverdueBalanceIsNotMovedOntoAHeldInvoice(): void
    {
        $old = $this->overdueInvoice(strtotime('-30 days'));
        $held = $this->renewalInvoice();
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($old->id)->status, 'still payable, with its reminders');
        $this->assertFalse(InvoiceItem::find()->where(['invoice_id' => $held->id, 'item_type' => InvoiceItem::TYPE_ARREARS])->exists());

        // Sending the held invoice moves the overdue balance onto it.
        (new InvoiceReviewService())->release(Invoice::findOne($held->id));
        $this->assertSame(Invoice::STATUS_CARRIED_FORWARD, Invoice::findOne($old->id)->status);
        $this->assertEqualsWithDelta((float) $held->total_payable + 500, (float) Invoice::findOne($held->id)->total_payable, 0.001);
    }

    public function testAnOverdueBalanceMovesOnlyAfterTheGracePeriod(): void
    {
        $this->setReview(false);
        $recent = $this->overdueInvoice(strtotime('-5 days'));
        $this->renewalInvoice();
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($recent->id)->status, 'sent 5 days ago: the customer still has time');

        Invoice::updateAll(['created_at' => strtotime('-20 days')], ['id' => $recent->id]);
        $this->renewalInvoice();
        $this->assertSame(Invoice::STATUS_CARRIED_FORWARD, Invoice::findOne($recent->id)->status);
    }

    private function overdueInvoice(int $createdAt): Invoice
    {
        $invoice = new Invoice();
        $invoice->setAttributes(['user_id' => $this->_user->id, 'invoice_number' => 'TEST-OLD-' . bin2hex(random_bytes(4)), 'client_name' => 'Review Test', 'subtotal_amount' => 500, 'discount_amount' => 0, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 0, 'total_payable' => 500, 'status' => Invoice::STATUS_UNPAID, 'due_date' => date('Y-m-d', strtotime('-3 days')), 'created_at' => $createdAt, 'updated_at' => $createdAt], false);
        $this->assertTrue($invoice->save(false));

        return $invoice;
    }

    public function testWithTheStepOffInvoicesAreIssuedAtOnce(): void
    {
        $this->setReview(false);
        $this->assertSame(Invoice::STATUS_UNPAID, $this->renewalInvoice()->status);
    }

    private function setReview(bool $on): void
    {
        $exists = (new \yii\db\Query())->from('{{%site_setting}}')->where(['id' => 1])->exists();
        $exists
            ? Yii::$app->db->createCommand()->update('{{%site_setting}}', ['review_invoices' => (int) $on], ['id' => 1])->execute()
            : Yii::$app->db->createCommand()->insert('{{%site_setting}}', ['id' => 1, 'site_name' => 'Test', 'site_url' => 'https://example.test', 'mobile_1' => 9999999999, 'email_1' => 'test@example.test', 'review_invoices' => (int) $on, 'updated_at' => time()])->execute();
        $this->resetSettingsCache();
    }

    private function resetSettingsCache(): void
    {
        (new \ReflectionProperty(CustomYii::class, '_settings'))->setValue(null, null);
    }

    private function renewalInvoice(): Invoice
    {
        if (!CustomerBillingProfile::find()->where(['user_id' => $this->_user->id])->exists()) {
            $profile = new CustomerBillingProfile();
            $profile->setAttributes(['user_id' => $this->_user->id, 'legal_name' => 'Review Test', 'state' => 'Delhi', 'state_code' => '07', 'is_gst_applicable' => 0, 'default_discount_amount' => 0, 'created_at' => time(), 'updated_at' => time()], false);
            $this->assertTrue($profile->save(false));
        }
        $run = new BillingRun();
        $run->setAttributes(['run_number' => 'TEST-RUN-' . bin2hex(random_bytes(4)), 'status' => BillingRun::STATUS_RUNNING, 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($run->save(false));
        $event = new BillingEvent();
        $event->setAttributes(['billing_run_id' => $run->id, 'user_id' => $this->_user->id, 'item_type' => InvoiceItem::TYPE_HOSTING, 'item_id' => random_int(100000, 999999), 'description' => 'Hosting', 'period_start' => '2026-12-01', 'period_end' => '2027-11-30', 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => '2026-12-01', 'status' => BillingEvent::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($event->save(false));

        $invoice = (new AutoInvoiceService())->generateForUser((int) $run->id, (int) $this->_user->id);
        $this->assertNotNull($invoice);

        return $invoice;
    }
}
