<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\BillingEvent;
use common\models\CustomerBillingProfile;
use common\models\CustomerNotification;
use common\models\Hosting;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\LoginForm;
use common\models\ServiceRenewalHistory;
use common\models\SupportTicket;
use common\models\Transaction;
use common\models\User;
use common\services\admin\AdminAlertService;
use common\services\billing\InvoicePaymentService;
use common\services\billing\InvoiceReminderService;
use common\services\billing\PaymentProcessingService;
use common\services\billing\RenewalFulfilmentService;
use common\services\billing\ServiceRenewalReminderService;
use common\services\customer\IncompleteProfileReminderService;
use common\services\payment\RazorpayService;
use frontend\models\ResendVerificationEmailForm;
use Yii;
use yii\db\Transaction as DbTransaction;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

/** Account, alert, renewal and payment rules added together with the login-first billing profile. */
final class LifecycleRulesTest extends Unit
{
    private const RAZORPAY = ['enabled' => true, 'keyId' => 'rzp_test_x', 'keySecret' => 'secret', 'webhookSecret' => 'hook'];

    protected $tester;
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $this->_user = $this->customer();
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testASuspendedCustomerIsToldWhyOnlyWithTheRightPassword(): void
    {
        $this->_user->updateAttributes(['status' => User::getStatusSuspended()]);
        $error = function (string $password): string {
            $form = new LoginForm(['username' => $this->_user->username, 'password' => $password]);
            $this->assertFalse($form->login());

            return (string) $form->getFirstError('password');
        };
        $this->assertStringContainsString('Incorrect', $error('WrongPassword1'));
        $this->assertStringContainsString('disabled', $error('LifecycleTestPassword'));
    }

    public function testResendingVerificationClearsThePasswordChosenAtSignup(): void
    {
        $this->_user->generateEmailVerificationToken();
        $this->_user->updateAttributes(['status' => User::getStatusInactive(), 'verification_token' => $this->_user->verification_token]);
        $form = new ResendVerificationEmailForm(['email' => $this->_user->email]);
        $this->assertTrue($form->sendEmail(Yii::$app->mailer, 'support@example.test', 'Test'));

        $this->assertFalse(User::findOne($this->_user->id)->validatePassword('LifecycleTestPassword'));
        $mails = $this->tester->grabSentEmails();
        $this->assertStringContainsString('Forgot password?', (string) end($mails)->toString());
    }

    public function testResetLinksDieWithThePasswordOrEmailAndCookiesWithTheSuspension(): void
    {
        $user = User::findOne($this->_user->id);
        $user->generatePasswordResetToken();
        $user->save(false);
        $user->email = 'moved-' . bin2hex(random_bytes(4)) . '@example.test';
        $user->save(false);
        $this->assertNull(User::findOne($user->id)->password_reset_token);

        $key = $user->auth_key;
        $user->status = User::getStatusSuspended();
        $user->save(false);
        $this->assertNotSame($key, User::findOne($user->id)->auth_key);
    }

    public function testATicketOrHeldPaymentKeepsACustomerInUse(): void
    {
        $id = (int) $this->_user->id;
        $this->assertFalse(IncompleteProfileReminderService::isInUse($id));

        $ticket = new SupportTicket();
        $ticket->setAttributes(['user_id' => $id, 'ticket_number' => 'TEST-' . bin2hex(random_bytes(4)), 'subject' => 'Help', 'message' => 'Help', 'status' => SupportTicket::STATUS_OPEN, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($ticket->save(false));
        $this->assertTrue(IncompleteProfileReminderService::isInUse($id));
        $this->assertFalse(IncompleteProfileReminderService::isInUse($id, false), 'a ticket does not unlock the portal');
        $ticket->updateAttributes(['status' => SupportTicket::STATUS_CLOSED]);
        $this->assertFalse(IncompleteProfileReminderService::isInUse($id));

        $invoice = $this->invoice(1000.00, Invoice::STATUS_CANCELLED);
        $this->assertFalse(IncompleteProfileReminderService::isInUse($id));
        $this->payment($invoice, ['is_unapplied' => 1]);
        $this->assertTrue(IncompleteProfileReminderService::isInUse($id));
    }

    public function testCompletingTheProfileClearsTheReminderAndWarning(): void
    {
        $bell = new CustomerNotification();
        $bell->setAttributes(['user_id' => $this->_user->id, 'type' => 'account', 'title' => IncompleteProfileReminderService::TITLE, 'message' => 'x', 'is_read' => 0, 'created_at' => time()], false);
        $this->assertTrue($bell->save(false));
        $this->_user->updateAttributes(['profile_warned_at' => time()]);

        $this->profile();

        $this->assertSame(1, (int) CustomerNotification::findOne($bell->id)->is_read);
        $this->assertNull(User::findOne($this->_user->id)->profile_warned_at);
    }

    public function testAFailedAlertEmailIsSentAgainByTheNextCronJob(): void
    {
        AdminNotification::updateAll(['emailed_at' => time()], ['emailed_at' => null]);
        $row = new AdminNotification();
        $row->setAttributes(['type' => 'billing', 'title' => 'Unit Retry', 'message' => 'x', 'is_read' => 0, 'created_at' => time() - 3600], false);
        $this->assertTrue($row->save(false));
        $before = count($this->tester->grabSentEmails());

        AdminAlertService::retryUnsent();
        $this->assertCount($before + 1, $this->tester->grabSentEmails());
        $this->assertNotNull(AdminNotification::findOne($row->id)->emailed_at);

        AdminAlertService::retryUnsent();
        $this->assertCount($before + 1, $this->tester->grabSentEmails());
    }

    public function testAFailedInvoiceIsAlertedOnceADayPerCustomer(): void
    {
        $count = static fn(): int => (int) AdminNotification::find()->where(['title' => 'Automatic Invoice Failed'])->andWhere(['like', 'message', 'Client ID: 98765'])->count();
        $before = $count();
        AdminAlertService::invoiceGenerationFailed(987651, 'x');
        AdminAlertService::invoiceGenerationFailed(987651, 'x');
        AdminAlertService::invoiceGenerationFailed(987652, 'x');
        $this->assertSame($before + 2, $count());
    }

    public function testNoReminderOnTheDayAnInvoiceIsIssued(): void
    {
        $invoice = $this->invoice(1000.00);
        $invoice->updateAttributes(['due_date' => date('Y-m-d')]);
        $logged = static fn(): int => (int) \common\models\InvoiceReminderLog::find()->where(['invoice_id' => $invoice->id])->count();
        (new InvoiceReminderService())->sendDueReminders();
        $this->assertSame(0, $logged());

        $invoice->updateAttributes(['created_at' => strtotime('-1 day')]);
        (new InvoiceReminderService())->sendDueReminders();
        $this->assertSame(1, $logged());

        // Issued on a reminder day, after that day's run: no catch-up the next morning.
        $target = new \DateTimeImmutable('+6 days midnight');
        $this->assertNull(ServiceRenewalReminderService::dueThreshold(6, [7], $target, (int) strtotime('-1 day 15:00')));
        $this->assertSame(7, ServiceRenewalReminderService::dueThreshold(6, [7], $target, (int) strtotime('-5 days')));
    }

    public function testAnAnchorIsOnlyResyncedWhenThePaymentMovedTheExpiry(): void
    {
        // Extended by hand past this renewal: the anchor stays.
        $extended = $this->hosting('2027-06-15', 31);
        $invoice = $this->invoice(1000.00);
        $this->event($invoice, $extended, '2026-11-30', '2026-12-30', 1);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));
        $this->assertSame(31, (int) Hosting::findOne($extended->id)->renewal_anchor_day);
        $this->assertSame('2027-06-15', Hosting::findOne($extended->id)->current_expiry_date);

        // A whole year ending in leap-year February says nothing about the anchor.
        $leap = $this->hosting('2027-02-28', 28);
        $invoice = $this->invoice(1000.00);
        $this->event($invoice, $leap, '2027-02-28', '2028-02-29', 12);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));
        $this->assertSame(28, (int) Hosting::findOne($leap->id)->renewal_anchor_day);
    }

    public function testPayingASuspendedServiceTellsStaff(): void
    {
        $hosting = $this->hosting('2026-11-30', null);
        $hosting->updateAttributes(['status' => Hosting::STATUS_SUSPENDED]);
        $invoice = $this->invoice(1000.00);
        $this->event($invoice, $hosting, '2026-11-30', '2027-11-30', 12);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));

        $this->assertSame(Hosting::STATUS_ACTIVE, (int) Hosting::findOne($hosting->id)->status);
        $alert = AdminNotification::find()->where(['title' => 'Service Reactivated By Payment'])->andWhere(['like', 'message', $hosting->primary_domain]);
        $this->assertFalse($alert->exists(), 'not while the transaction is open');
        Yii::$app->db->trigger(\yii\db\Connection::EVENT_COMMIT_TRANSACTION); // this test runs inside a transaction that never commits
        $this->assertTrue($alert->exists());
    }

    public function testRefundIsDecidedPerRenewalAlongItsOwnInvoices(): void
    {
        $hosting = $this->hosting('2026-01-01', null);
        // A (₹300 kept) carried to B, carried to C: every renewal paid on C, all closed as refunded.
        $a = $this->invoice(1000.00, Invoice::STATUS_REFUNDED);
        $b = $this->carried($a, Invoice::STATUS_REFUNDED);
        $c = $this->carried($b, Invoice::STATUS_REFUNDED);
        $this->payment($a, ['amount' => 300.00]);
        $onA = $this->event($a, $hosting, '2026-01-01', '2027-01-01', 12);
        $onC = $this->event($c, $hosting, '2027-01-01', '2028-01-01', 12);
        foreach ([$onA, $onC] as $event) {
            $event->updateAttributes(['status' => BillingEvent::STATUS_FULFILLED]);
            $history = new ServiceRenewalHistory();
            $history->setAttributes(['billing_event_id' => $event->id, 'item_type' => 'hosting', 'item_id' => $hosting->id, 'invoice_id' => $c->id, 'billed_amount' => 1000, 'renewal_period_months' => 12, 'start_date' => $event->period_start, 'end_date' => $event->period_end, 'logged_at' => time()], false);
            $this->assertTrue($history->save(false));
        }

        $this->assertSame([(int) $onC->id], (new RenewalFulfilmentService())->refundedEventIds(InvoiceItem::TYPE_HOSTING, (int) $hosting->id));
        $this->assertSame([(int) $onC->id], (new RenewalFulfilmentService())->refundedEventIdsOfCustomer((int) $this->_user->id));
    }

    public function testAOneTimePriceBillsAServiceWithNoBasePrice(): void
    {
        $hosting = $this->hosting('2030-06-30', null);
        $hosting->renewal_base_amount = 0;
        $hosting->next_renewal_amount = 750;
        $this->profile();
        $state = (new InvoicePaymentService())->renewalState($hosting, InvoiceItem::TYPE_HOSTING);
        $this->assertSame((bool) \common\models\BillingSettings::forType(InvoiceItem::TYPE_HOSTING)->is_active, $state['billable']);
    }

    public function testClosingAsRefundedIsRefusedWhileACarriedInvoiceKeepsMoney(): void
    {
        $source = $this->invoice(1000.00, Invoice::STATUS_CARRIED_FORWARD);
        $kept = $this->payment($source, ['amount' => 300.00]);
        $target = $this->carried($source);
        $service = new InvoicePaymentService();

        $this->assertNotNull($service->closeBlocker($target));
        $this->assertNull($service->closeBlocker($target, $kept->id), 'that payment is the one being refunded');
    }

    public function testPayOnlineFindsAnEarlierOrderThatWasPaid(): void
    {
        $invoice = $this->invoice(1000.00);
        $orderId = 'order_rec_' . bin2hex(random_bytes(4));
        $order = $this->payment($invoice, ['status' => Transaction::STATUS_PENDING, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $orderId, 'paid_at' => null]);
        $http = new MockTransport();
        $http->appendResponse($this->reply(['items' => [['id' => 'pay_rec', 'status' => 'failed', 'amount' => 100000]]]));
        $this->assertSame(0, (new RazorpayService(self::RAZORPAY, $http))->recoverPaidOrders((int) $invoice->id));

        $http->appendResponse($this->reply(['items' => [['id' => 'pay_rec', 'status' => 'captured', 'amount' => 100000]]]));
        $this->assertSame(1, (new RazorpayService(self::RAZORPAY, $http))->recoverPaidOrders((int) $invoice->id));
        $this->assertSame(Transaction::STATUS_CAPTURED, Transaction::findOne($order->id)->status);
        $this->assertSame(Invoice::STATUS_PAID, Invoice::findOne($invoice->id)->status);
        $this->assertTrue(CustomerNotification::find()->where(['user_id' => $this->_user->id, 'title' => 'Payment Confirmed'])->exists());
    }

    public function testAReplayRetriesAConfirmationThatFailedAndSendsOnlyOne(): void
    {
        $invoice = $this->invoice(1000.00);
        $orderId = 'order_rep_' . bin2hex(random_bytes(4));
        $order = $this->payment($invoice, ['status' => Transaction::STATUS_PENDING, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $orderId, 'paid_at' => null]);
        $service = new RazorpayService(self::RAZORPAY);
        $service->capture($orderId, 'pay_rep', 100000);
        $this->assertNotNull(Transaction::findOne($order->id)->confirmation_emailed_at);
        $sent = count($this->tester->grabSentEmails());

        $service->capture($orderId, 'pay_rep', 100000);
        $this->assertCount($sent, $this->tester->grabSentEmails(), 'already emailed');

        Transaction::updateAll(['confirmation_emailed_at' => null], ['id' => $order->id]);
        $service->capture($orderId, 'pay_rep', 100000);
        $this->assertCount($sent + 1, $this->tester->grabSentEmails());
    }

    public function testReactivationIsDatedOnlyByStaffBringingAnAccountBack(): void
    {
        $user = User::findOne($this->_user->id);
        $user->mobile = '9876543210';
        $user->save(false);
        $this->assertNull(User::findOne($user->id)->reactivated_at, 'editing details buys no time');
        $user->status = User::getStatusSuspended();
        $user->save(false);
        $user->status = User::getStatusActive();
        $user->save(false);
        $this->assertNotNull(User::findOne($user->id)->reactivated_at);

        // Staff-set Inactive counts too; a customer verifying their email doesn't.
        User::updateAll(['reactivated_at' => null, 'status' => User::getStatusInactive()], ['id' => $user->id]);
        $staffSet = User::findOne($user->id);
        $staffSet->status = User::getStatusActive();
        $staffSet->save(false);
        $this->assertNotNull(User::findOne($user->id)->reactivated_at);

        User::updateAll(['reactivated_at' => null, 'status' => User::getStatusInactive(), 'verification_token' => 'token_' . time()], ['id' => $user->id]);
        $verifying = User::findOne($user->id);
        $verifying->status = User::getStatusActive();
        $verifying->save(false);
        $this->assertNull(User::findOne($user->id)->reactivated_at);
    }

    public function testARenewalBilledFromTheBasePriceDoesNotRestoreABaseStaffBlanked(): void
    {
        $hosting = $this->hosting('2026-11-30', null);
        $hosting->updateAttributes(['renewal_base_amount' => 0]);
        $invoice = $this->invoice(1000.00);
        $this->event($invoice, $hosting, '2026-12-01', '2027-11-30', 12);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));
        $this->assertEqualsWithDelta(0.0, (float) Hosting::findOne($hosting->id)->renewal_base_amount, 0.001);
    }

    public function testACouponOnACarriedInvoiceIsFreedOnceTheKeptMoneyGoesBack(): void
    {
        $source = $this->invoice(1000.00, Invoice::STATUS_REFUNDED);
        $target = $this->carried($source, Invoice::STATUS_REFUNDED);
        $coupon = new \common\models\Coupon();
        $coupon->setAttributes(['name' => 'Life', 'code' => 'LIFE' . strtoupper(bin2hex(random_bytes(3))), 'type' => \common\models\Coupon::TYPE_F, 'amount' => 10, 'is_active' => \common\models\Coupon::IS_ACTIVE_Y, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($coupon->save(false));
        $redemption = new \common\models\CouponRedemption();
        $redemption->setAttributes(['coupon_code' => $coupon->code, 'coupon_id' => $coupon->id, 'user_id' => $this->_user->id, 'invoice_id' => $source->id, 'discount_amount' => 10, 'redeemed_at' => time(), 'created_at' => time()], false);
        $this->assertTrue($redemption->save(false));

        $kept = $this->payment($target, ['amount' => 500.00]);
        (new InvoicePaymentService())->releaseCoupons($target);
        $this->assertNotNull(\common\models\CouponRedemption::findOne(['invoice_id' => $source->id]));

        $kept->updateAttributes(['status' => Transaction::STATUS_REFUNDED]);
        (new InvoicePaymentService())->releaseCoupons($target);
        $this->assertNull(\common\models\CouponRedemption::findOne(['invoice_id' => $source->id]));
    }

    public function testAnOldHistoryRowWithNoRenewalGoesByItsInvoice(): void
    {
        $hosting = $this->hosting('2026-01-01', null);
        $invoice = $this->invoice(1000.00, Invoice::STATUS_REFUNDED);
        $history = new ServiceRenewalHistory();
        $history->setAttributes(['billing_event_id' => null, 'item_type' => 'hosting', 'item_id' => $hosting->id, 'invoice_id' => $invoice->id, 'billed_amount' => 1000, 'renewal_period_months' => 12, 'start_date' => '2025-01-01', 'end_date' => '2026-01-01', 'logged_at' => time()], false);
        $this->assertTrue($history->save(false));
        $shown = fn(): bool => ServiceRenewalHistory::find()->alias('rh')->where(['rh.id' => $history->id])->andWhere((new RenewalFulfilmentService())->historyNotRefunded('rh', (int) $this->_user->id))->exists();

        $this->assertFalse($shown());
        $this->payment($invoice, ['amount' => 100.00]);
        $this->assertTrue($shown());
    }

    public function testAnOldAccountReactivatedLongAgoCanBeDisabledButEditingDetailsDoesNotDelayIt(): void
    {
        $old = strtotime('-2 months');
        $this->_user->updateAttributes(['created_at' => $old, 'updated_at' => time(), 'profile_warned_at' => strtotime('-8 days')]);
        (new IncompleteProfileReminderService())->sendReminders();
        $this->assertSame(User::getStatusSuspended(), (int) User::findOne($this->_user->id)->status);
    }

    public function testAOneTimePriceBecomesTheBasePriceOfAServiceWithNone(): void
    {
        $hosting = $this->hosting('2026-11-30', null);
        $hosting->updateAttributes(['renewal_base_amount' => 0, 'next_renewal_amount' => 3000]);
        $invoice = $this->invoice(3000.00);
        $this->event($invoice, $hosting, '2026-12-01', '2027-11-30', 12)->updateAttributes(['amount' => 3000, 'is_manual_amount' => 1]);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));

        $fresh = Hosting::findOne($hosting->id);
        $this->assertEqualsWithDelta(3000.00, (float) $fresh->renewal_base_amount, 0.001);
        $this->assertNull($fresh->next_renewal_amount);
    }

    public function testPayingAgainBringsBackAServiceWhoseRenewalWasAlreadyGranted(): void
    {
        $hosting = $this->hosting('2027-11-30', null);
        $hosting->updateAttributes(['status' => Hosting::STATUS_SUSPENDED]);
        $invoice = $this->invoice(1000.00);
        $this->event($invoice, $hosting, '2026-12-01', '2027-11-30', 12)->updateAttributes(['status' => BillingEvent::STATUS_FULFILLED]);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice));
        $this->assertSame(Hosting::STATUS_ACTIVE, (int) Hosting::findOne($hosting->id)->status);

        // Already Paid before this change: a later refund others still cover must not undo a suspension.
        Hosting::updateAll(['status' => Hosting::STATUS_SUSPENDED], ['id' => $hosting->id]);
        (new PaymentProcessingService())->transactionChanged($this->payment($invoice, ['amount' => 1.00]));
        $this->assertSame(Hosting::STATUS_SUSPENDED, (int) Hosting::findOne($hosting->id)->status);
    }

    public function testACarriedInvoicesCouponStaysUsedWhenTheNewerInvoiceKeptTheMoney(): void
    {
        $source = $this->invoice(1000.00, Invoice::STATUS_CARRIED_FORWARD);
        $target = $this->carried($source);
        $this->payment($target, ['amount' => 500.00]);
        $coupon = new \common\models\Coupon();
        $coupon->setAttributes(['name' => 'Life', 'code' => 'LIFE' . strtoupper(bin2hex(random_bytes(3))), 'type' => \common\models\Coupon::TYPE_F, 'amount' => 10, 'is_active' => \common\models\Coupon::IS_ACTIVE_Y, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($coupon->save(false));
        $redemption = new \common\models\CouponRedemption();
        $redemption->setAttributes(['coupon_code' => $coupon->code, 'coupon_id' => $coupon->id, 'user_id' => $this->_user->id, 'invoice_id' => $source->id, 'discount_amount' => 10, 'redeemed_at' => time(), 'created_at' => time()], false);
        $this->assertTrue($redemption->save(false));

        (new InvoicePaymentService())->closeAsRefunded($target, null, true);

        $this->assertNotNull(\common\models\CouponRedemption::findOne(['invoice_id' => $source->id]));
    }

    public function testAPaymentFoundLateIsDatedWhenItWasPaidAndHeldIfTheInvoiceIsClosed(): void
    {
        $invoice = $this->invoice(1000.00);
        $orderId = 'order_late_' . bin2hex(random_bytes(4));
        $order = $this->payment($invoice, ['status' => Transaction::STATUS_PENDING, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $orderId, 'paid_at' => null]);
        $paidAt = strtotime('-10 days');
        (new RazorpayService(self::RAZORPAY))->capture($orderId, 'pay_late', 100000, null, $paidAt);
        $this->assertSame($paidAt, (int) Transaction::findOne($order->id)->paid_at);

        $closed = $this->invoice(1000.00, Invoice::STATUS_CANCELLED);
        $heldOrder = 'order_held_' . bin2hex(random_bytes(4));
        $held = $this->payment($closed, ['status' => Transaction::STATUS_PENDING, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $heldOrder, 'paid_at' => null]);
        (new RazorpayService(self::RAZORPAY))->capture($heldOrder, 'pay_held', 100000);
        $this->assertSame(1, (int) Transaction::findOne($held->id)->is_unapplied);
        $mails = $this->tester->grabSentEmails();
        $this->assertStringContainsString('not yet applied', (string) end($mails)->getSubject());
    }

    public function testPayOnlineStopsWhenAPaidOrderCannotBeRecorded(): void
    {
        $invoice = $this->invoice(1000.00);
        $order = 'order_stuck_' . bin2hex(random_bytes(4));
        $this->payment($invoice, ['status' => Transaction::STATUS_PENDING, 'gateway_id' => Transaction::GATEWAY_RAZORPAY, 'gateway_order_id' => $order, 'paid_at' => null]);
        $http = new MockTransport();
        $http->appendResponse($this->reply(['items' => [['id' => 'pay_stuck', 'status' => 'captured', 'amount' => 999]]])); // amount differs
        $service = new RazorpayService(self::RAZORPAY, $http);

        $this->assertSame(0, $service->recoverPaidOrders((int) $invoice->id));
        $this->assertTrue($service->paidButNotRecorded);
        $this->assertTrue(AdminNotification::find()->where(['title' => 'Online Payment Not Recorded'])->andWhere(['like', 'message', 'pay_stuck'])->exists());
    }

    public function testARefundRetrySendsTheSameKey(): void
    {
        $http = new MockTransport();
        $service = new RazorpayService(self::RAZORPAY, $http);
        foreach ([1, 2] as $attempt) {
            $http->appendResponse($this->reply(['status' => 'captured', 'amount_refunded' => 0]));
            $http->appendResponse($this->reply(['id' => 'rfnd_1']));
            $this->assertSame('rfnd_1', $service->refundPayment('pay_key', 50000, 0));
        }
        $keys = [];
        foreach ($http->flushRequests() as $request) {
            if ($request->getMethod() === 'POST' || strtoupper($request->getMethod()) === 'POST') {
                $keys[] = $request->getHeaders()->get('X-Refund-Idempotency');
            }
        }
        $this->assertSame(['refund-pay_key-0-50000', 'refund-pay_key-0-50000'], $keys);
    }

    public function testACustomerSeesTheirNotesNotStaffOnes(): void
    {
        $payment = new Transaction(['user_payment_notes' => "UTR 123\nUNAPPLIED: invoice was Paid when approved.\nRefunded ₹10.00 on 01 Jan 2027.\nApplied to invoice X on 01 Jan 2027 (paid on Y)."]);
        $this->assertSame('UTR 123', $payment->customerNotes());
    }

    private function reply(array $data): Response
    {
        return new Response(['content' => json_encode($data, JSON_THROW_ON_ERROR), 'headers' => ['http-code' => '200', 'content-type' => 'application/json']]);
    }

    private function customer(): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'life-' . $suffix, 'email' => 'life-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->password = 'LifecycleTestPassword';
        $this->assertTrue($user->save(), json_encode($user->errors));

        return $user;
    }

    private function profile(): void
    {
        $profile = new CustomerBillingProfile();
        $profile->setAttributes(['user_id' => $this->_user->id, 'legal_name' => 'Life Test', 'address_line_1' => '1 Road', 'city' => 'Delhi', 'state' => 'Delhi', 'state_code' => '07', 'postal_code' => '110001', 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($profile->save(false));
    }

    private function hosting(string $expiry, ?int $anchor): Hosting
    {
        $hosting = new Hosting();
        $hosting->setAttributes(['user_id' => $this->_user->id, 'primary_domain' => 'life-' . bin2hex(random_bytes(4)) . '.test', 'start_date' => '2020-01-01', 'current_expiry_date' => $expiry, 'renewal_anchor_day' => $anchor, 'renewal_base_amount' => 1000, 'status' => Hosting::STATUS_ACTIVE, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($hosting->save(false));

        return $hosting;
    }

    private function invoice(float $amount, string $status = Invoice::STATUS_UNPAID): Invoice
    {
        $invoice = new Invoice();
        $invoice->setAttributes(['user_id' => $this->_user->id, 'invoice_number' => 'TEST-LIFE-' . bin2hex(random_bytes(4)), 'client_name' => 'Life Test', 'subtotal_amount' => $amount, 'discount_amount' => 0, 'total_payable' => $amount, 'status' => $status, 'due_date' => date('Y-m-d', strtotime('+7 days')), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($invoice->save(false));

        return $invoice;
    }

    private function carried(Invoice $source, string $status = Invoice::STATUS_UNPAID): Invoice
    {
        $target = $this->invoice(1000.00, $status);
        $line = new InvoiceItem();
        $line->setAttributes(['invoice_id' => $target->id, 'item_type' => InvoiceItem::TYPE_ARREARS, 'source_invoice_id' => $source->id, 'category' => 'Arrears', 'description' => 'Carried', 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000], false);
        $this->assertTrue($line->save(false));

        return $target;
    }

    private function event(Invoice $invoice, Hosting $hosting, string $start, string $end, int $months): BillingEvent
    {
        $event = new BillingEvent();
        $event->setAttributes(['user_id' => $this->_user->id, 'invoice_id' => $invoice->id, 'item_type' => InvoiceItem::TYPE_HOSTING, 'item_id' => $hosting->id, 'description' => "Renewal {$months}", 'period_start' => $start, 'period_end' => $end, 'previous_amount' => 0, 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => date('Y-m-d'), 'status' => BillingEvent::STATUS_INVOICED, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($event->save(false));

        return $event;
    }

    private function payment(Invoice $invoice, array $attributes = []): Transaction
    {
        $payment = new Transaction();
        $payment->setAttributes($attributes + ['invoice_id' => $invoice->id, 'gateway_id' => Transaction::GATEWAY_UPI_BANK, 'amount' => (float) $invoice->total_payable, 'status' => Transaction::STATUS_CAPTURED, 'is_unapplied' => 0, 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($payment->save(false));

        return $payment;
    }
}
