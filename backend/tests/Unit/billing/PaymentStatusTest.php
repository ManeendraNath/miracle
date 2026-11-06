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

    public function testPayingASuspendedHostingRenewalReactivatesIt(): void
    {
        $hosting = new \common\models\Hosting();
        $hosting->setAttributes(['user_id' => $this->_user->id, 'primary_domain' => 'suspended-' . bin2hex(random_bytes(4)) . '.test', 'current_expiry_date' => '2026-11-30', 'status' => \common\models\Hosting::STATUS_SUSPENDED, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($hosting->save(false));
        $invoice = $this->invoice(1000.00);
        $this->billingEvent($invoice)->updateAttributes(['item_id' => $hosting->id]);

        $payment = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        (new PaymentProcessingService())->transactionChanged($payment);

        $hosting->refresh();
        $this->assertSame(['2027-11-30', \common\models\Hosting::STATUS_ACTIVE], [(string) $hosting->current_expiry_date, (int) $hosting->status]);
    }

    public function testWritingOffReleasesACarriedInvoiceAndKeepsItsMoney(): void
    {
        $source = $this->invoice(1000.00);
        $this->payment($source, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 300.00);
        $source->updateAttributes(['status' => Invoice::STATUS_CARRIED_FORWARD]);
        $target = $this->carry($source);
        $service = new InvoicePaymentService();

        $this->assertNotNull($service->closeBlocker($target), 'closing as refunded would call kept money refunded');
        $this->assertNull($service->closeBlocker($target, null, true), 'money kept on the older invoice no longer strands the newer one');
        $service->closeAsRefunded($target, null, true);

        $this->assertSame(Invoice::STATUS_REFUNDED, Invoice::findOne($target->id)->status);
        $this->assertSame(Invoice::STATUS_REFUNDED, Invoice::findOne($source->id)->status);
        $this->assertEqualsWithDelta(300.00, $service->getCapturedAmount((int) $source->id), 0.001);
    }

    public function testAPartPaidInvoiceCanOnlyBeClosedKeepingMoneyByWritingOff(): void
    {
        $invoice = $this->invoice(1000.00);
        $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 300.00);
        $service = new InvoicePaymentService();

        $this->assertNotNull($service->closeBlocker($invoice));
        $this->assertNull($service->closeBlocker($invoice, null, true));
    }

    public function testARenewalOnACarriedInvoiceStopsCountingAsPaidOnceWrittenOff(): void
    {
        $source = $this->invoice(1000.00);
        $source->updateAttributes(['status' => Invoice::STATUS_CARRIED_FORWARD]);
        $target = $this->carry($source);
        $refunded = static fn(): array => \common\services\billing\RenewalFulfilmentService::fullyRefundedInvoices();
        $this->assertNotContains((int) $source->id, $refunded());

        (new InvoicePaymentService())->closeAsRefunded($target, null, true);

        $this->assertContains((int) $source->id, $refunded());
    }

    public function testRenewalStateFindsTheInvoiceBillingAService(): void
    {
        $service = new \common\models\Domains();
        $service->setAttributes(['user_id' => $this->_user->id, 'status' => 1, 'renewal_base_amount' => 500, 'current_expiry_date' => '2030-06-30'], false);
        $service->id = 987654;
        $settings = \common\models\BillingSettings::forType(InvoiceItem::TYPE_DOMAIN);
        $this->assertFalse((new InvoicePaymentService())->renewalState($service, InvoiceItem::TYPE_DOMAIN)['billable'], 'no billing details, no invoice is coming');
        $profile = new \common\models\CustomerBillingProfile();
        $profile->setAttributes(['user_id' => $this->_user->id, 'legal_name' => 'Pay Test', 'state' => 'Delhi', 'state_code' => '07', 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($profile->save(false));
        $state = (new InvoicePaymentService())->renewalState($service, InvoiceItem::TYPE_DOMAIN);
        $this->assertNull($state['invoiceNumber']);
        $this->assertSame(date('Y-m-d', strtotime('2030-06-30 -' . (int) $settings->invoice_before_days . ' days')), $state['issuedFrom']);
        $this->assertSame((bool) $settings->is_active, $state['billable']);

        $service->renewal_base_amount = 0;
        $this->assertFalse((new InvoicePaymentService())->renewalState($service, InvoiceItem::TYPE_DOMAIN)['billable'], 'no price: no invoice will come');

        $invoice = $this->invoice(500.00);
        $line = new InvoiceItem();
        $line->setAttributes(['invoice_id' => $invoice->id, 'item_type' => InvoiceItem::TYPE_DOMAIN, 'item_id' => 987654, 'category' => 'Domain', 'description' => 'Renewal', 'quantity' => 1, 'unit_price' => 500, 'total_price' => 500], false);
        $this->assertTrue($line->save(false));
        $this->assertSame($invoice->invoice_number, (new InvoicePaymentService())->renewalState($service, InvoiceItem::TYPE_DOMAIN)['invoiceNumber']);

        $invoice->updateAttributes(['status' => Invoice::STATUS_CARRIED_FORWARD]);
        $newer = $this->carry($invoice);
        $this->assertSame($newer->invoice_number, (new InvoicePaymentService())->payableInvoiceForService((int) $this->_user->id, InvoiceItem::TYPE_DOMAIN, 987654)->invoice_number, 'followed to the invoice holding the balance');
    }

    public function testDeletedCustomersAreNotBilled(): void
    {
        $service = new \common\models\Domains();
        $service->setAttributes(['user_id' => $this->_user->id, 'domain_url' => 'deleted-' . bin2hex(random_bytes(4)) . '.example.test', 'registrar' => 'Test', 'registered_date' => date('Y-m-d', strtotime('-1 year')), 'status' => 1, 'renewal_base_amount' => 500, 'current_expiry_date' => date('Y-m-d'), 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($service->save(false));
        $collected = static fn(): array => array_map(static fn($renewal): int => $renewal->itemId, (new \common\services\billing\collector\DomainBillingCollector())->collect(new \DateTimeImmutable('today')));
        if (!(bool) \common\models\BillingSettings::forType(InvoiceItem::TYPE_DOMAIN)->is_active) {
            $this->markTestSkipped('Domain billing is switched off in this database.');
        }
        $this->assertContains((int) $service->id, $collected());

        $this->_user->updateAttributes(['status' => User::getStatusDeleted()]);

        $this->assertNotContains((int) $service->id, $collected());
    }

    public function testCancelIsRefusedWhileAPaymentAwaitsReview(): void
    {
        $invoice = $this->invoice(1000.00);
        $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        $this->expectExceptionMessage('awaiting review');
        (new \common\services\billing\InvoiceCancellationService())->cancel($invoice, 'test');
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

    public function testDisablingAServiceReportsAndReleasesItsRenewals(): void
    {
        $release = new \common\services\billing\RenewalReleaseService();

        // Carried onto a newer invoice: that one is named, and nothing is cancelled.
        $older = $this->invoice(400.00);
        $event = $this->billingEvent($older);
        $newer = $this->invoice(1000.00);
        $arrears = new InvoiceItem();
        $arrears->setAttributes(['invoice_id' => $newer->id, 'item_type' => InvoiceItem::TYPE_ARREARS, 'source_invoice_id' => $older->id, 'category' => 'Previous Outstanding', 'description' => 'Arrears', 'quantity' => 1, 'unit_price' => 400, 'total_price' => 400], false);
        $this->assertTrue($arrears->save(false));
        $older->status = Invoice::STATUS_CARRIED_FORWARD;
        $this->assertTrue($older->save(false));
        $this->assertStringContainsString($newer->invoice_number, (string) $release->releaseForDisabledService((int) $event->item_type, (int) $event->item_id));
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($newer->id)->status);

        // Only the service's own renewal invoice: a Manager can't cancel it, an Admin can.
        $invoice = $this->invoice(1000.00);
        $own = $this->billingEvent($invoice);
        InvoiceItem::updateAll(['item_type' => $own->item_type, 'item_id' => $own->item_id], ['invoice_id' => $invoice->id]);
        $this->assertStringContainsString($invoice->invoice_number, (string) $release->releaseForDisabledService((int) $own->item_type, (int) $own->item_id, false));
        $this->assertSame(Invoice::STATUS_UNPAID, Invoice::findOne($invoice->id)->status);
        $this->assertNull($release->releaseForDisabledService((int) $own->item_type, (int) $own->item_id));
        $this->assertSame(Invoice::STATUS_CANCELLED, Invoice::findOne($invoice->id)->status);
    }

    public function testAFullyRefundedRenewalNoLongerCountsAsPaid(): void
    {
        $invoice = $this->invoice(1000.00);
        $event = $this->billingEvent($invoice);
        $event->status = BillingEvent::STATUS_FULFILLED;
        $this->assertTrue($event->save(false));
        $history = new \common\models\ServiceRenewalHistory();
        $history->setAttributes(['billing_event_id' => $event->id, 'item_type' => 'hosting', 'item_id' => $event->item_id, 'invoice_id' => $invoice->id, 'billed_amount' => 1000, 'renewal_period_months' => 12, 'start_date' => $event->period_start, 'end_date' => $event->period_end, 'logged_at' => time()], false);
        $this->assertTrue($history->save(false));
        $kept = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 100.00);
        $invoice->status = Invoice::STATUS_REFUNDED;
        $this->assertTrue($invoice->save(false));
        $fulfilment = new \common\services\billing\RenewalFulfilmentService();
        $prices = new \common\services\billing\RenewalPriceCalculator();

        // Money kept: the customer still paid for it, and the next price builds on it.
        $this->assertSame([], $fulfilment->refundedEventIds((int) $event->item_type, (int) $event->item_id));
        $this->assertEquals(1000.00, $prices->calculate((int) $event->item_type, (int) $event->item_id, 500.00, 10.0)->amount);

        $kept->status = Transaction::STATUS_REFUNDED;
        $this->assertTrue($kept->save(false));
        $this->assertSame([(int) $event->id], $fulfilment->refundedEventIds((int) $event->item_type, (int) $event->item_id));
        $this->assertEquals(500.00, $prices->calculate((int) $event->item_type, (int) $event->item_id, 500.00, 10.0)->amount);
    }

    public function testAFullRefundReplayIsIdempotent(): void
    {
        $invoice = $this->invoice(1000.00);
        $order = $this->payment($invoice, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_PENDING, 'order_test_' . bin2hex(random_bytes(4)));
        $paymentId = 'pay_test_' . bin2hex(random_bytes(4));
        (new RazorpayService([]))->capture($order->gateway_order_id, $paymentId, 100000);
        Transaction::updateAll(['status' => Transaction::STATUS_REFUNDED, 'refunded_amount' => 1000], ['id' => $order->id]);

        $this->assertSame(Transaction::STATUS_REFUNDED, (new RazorpayService([]))->capture($order->gateway_order_id, $paymentId, 100000)->status);
    }

    public function testReminderWaitsWhileAProofIsUnderReview(): void
    {
        $invoice = $this->invoice(1000.00);
        $invoice->updateAttributes(['due_date' => date('Y-m-d'), 'created_at' => strtotime('-5 days')]);
        $reminders = static fn(): int => (int) \common\models\InvoiceReminderLog::find()->where(['invoice_id' => $invoice->id])->count();
        $proof = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        (new \common\services\billing\InvoiceReminderService())->sendDueReminders();
        $this->assertSame(0, $reminders());

        $proof->status = Transaction::STATUS_FAILED;
        $this->assertTrue($proof->save(false));
        (new \common\services\billing\InvoiceReminderService())->sendDueReminders();
        $this->assertSame(1, $reminders());
    }

    public function testAClosedInvoicePdfSaysSoAndHidesThePaymentDetails(): void
    {
        $invoice = $this->invoice(1000.00);
        $render = static fn(): string => Yii::$app->view->renderFile(Yii::getAlias('@common/mail/invoice-pdf.php'), ['invoice' => Invoice::findOne($invoice->id), 'capturedAmount' => 0.0, 'outstandingAmount' => 1000.0, 'carriedInto' => null]);
        $this->assertStringContainsString('Payment Details', $render());

        Invoice::updateAll(['status' => Invoice::STATUS_CANCELLED], ['id' => $invoice->id]);
        $this->assertStringContainsString('no longer payable', $render());
        $this->assertStringNotContainsString('Payment Details', $render());
    }

    public function testAWrittenOffInvoiceIsNotCalledRefunded(): void
    {
        $invoice = $this->invoice(1000.00);
        (new InvoicePaymentService())->closeAsRefunded($invoice, null, true);
        $this->assertSame('Closed (balance written off)', Invoice::findOne($invoice->id)->statusLabel());

        $refunded = $this->invoice(1000.00);
        (new InvoicePaymentService())->closeAsRefunded($refunded);
        $this->assertSame('Refunded', Invoice::findOne($refunded->id)->statusLabel());
    }

    public function testACarriedBalanceIsFollowedHoweverLongTheChain(): void
    {
        $first = $this->invoice(100.00);
        $link = $first;
        foreach (range(1, 12) as $n) {
            $link->updateAttributes(['status' => Invoice::STATUS_CARRIED_FORWARD]);
            $link = $this->carry($link);
        }

        $service = new InvoicePaymentService();
        $this->assertSame((int) $link->id, (int) $service->openInvoiceCarrying((int) $first->id)->id);
        $this->assertNotNull($first->carriedTo());
        $pdf = Yii::$app->view->renderFile(Yii::getAlias('@common/mail/invoice-pdf.php'), ['invoice' => Invoice::findOne($first->id), 'capturedAmount' => 0.0, 'outstandingAmount' => 0.0, 'carriedInto' => $link]);
        $this->assertStringContainsString('Balance moved to invoice ' . $link->invoice_number, $pdf);
    }

    public function testMoneyKeptOnACarriedSourceIsNotAFullRefund(): void
    {
        $source = $this->invoice(1000.00);
        $this->payment($source, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 300.00);
        $source->updateAttributes(['status' => Invoice::STATUS_CARRIED_FORWARD]);
        $target = $this->carry($source);
        $target->updateAttributes(['status' => Invoice::STATUS_REFUNDED]);

        $this->assertNotContains((int) $target->id, \common\services\billing\RenewalFulfilmentService::fullyRefundedInvoices());
    }

    public function testAClosedInvoiceCannotBeCancelled(): void
    {
        $invoice = $this->invoice(1000.00);
        $invoice->updateAttributes(['status' => Invoice::STATUS_REFUNDED]);

        $this->expectExceptionMessage('already closed');
        (new \common\services\billing\InvoiceCancellationService())->cancel($invoice);
    }

    public function testFulfilmentMovesAStaleAnchorToTheDateGranted(): void
    {
        $hosting = new \common\models\Hosting();
        $hosting->setAttributes(['user_id' => $this->_user->id, 'primary_domain' => 'anchor-' . bin2hex(random_bytes(4)) . '.test', 'current_expiry_date' => '2026-11-30', 'renewal_anchor_day' => 15, 'status' => \common\models\Hosting::STATUS_ACTIVE, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($hosting->save(false));
        $invoice = $this->invoice(1000.00);
        $this->billingEvent($invoice)->updateAttributes(['item_id' => $hosting->id]);

        (new PaymentProcessingService())->transactionChanged($this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED));

        $hosting->refresh();
        $this->assertSame(31, (int) $hosting->renewal_anchor_day, '30 Nov is a month end');
    }

    public function testRemindersPromiseAnInvoiceOnlyWhenOneIsComing(): void
    {
        /** @var \yii\mail\BaseMailer $mailer */
        $mailer = Yii::$app->mailer;
        $render = fn(string $view, bool $billable, int $days = 7): string => $mailer->render('@common/mail/renewal-reminder-' . $view, ['serviceName' => 'site.test', 'expiryDate' => '2027-01-01', 'daysBefore' => $days, 'client' => $this->_user, 'billable' => $billable], false);
        foreach (['html', 'text'] as $view) {
            $this->assertStringContainsString('We email your renewal invoice', $render($view, true));
            $this->assertStringNotContainsString('We email your renewal invoice', $render($view, false));
            $this->assertStringContainsString('contact us to renew', $render($view, false));
        }
        $this->assertStringContainsString('Expires today', $render('text', true, 0));
    }

    public function testPaymentsCanBeFoundByInvoiceNumberNewestFirst(): void
    {
        $invoice = $this->invoice(1000.00);
        $older = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_FAILED);
        $newer = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_FAILED);
        $this->payment($this->invoice(500.00), Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_FAILED);

        $found = (new \backend\models\searches\TransactionSearch())->search(['TransactionSearch' => ['invoice_number' => $invoice->invoice_number]])->getModels();
        $this->assertSame([(int) $newer->id, (int) $older->id], array_map(static fn(Transaction $t): int => (int) $t->id, $found));
    }

    public function testClosingWithMoneyKeptLeavesTheCouponUsed(): void
    {
        $service = new InvoicePaymentService();
        $code = 'KEPT' . strtoupper(bin2hex(random_bytes(4)));
        $coupon = new \common\models\Coupon();
        $coupon->setAttributes(['name' => $code, 'code' => $code, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($coupon->save(false));
        $redeemed = function (float $kept) use ($service, $code, $coupon): bool {
            $invoice = $this->invoice(1000.00);
            $invoice->coupon_code = $code;
            $this->assertTrue($invoice->save(false));
            $redemption = new \common\models\CouponRedemption();
            $redemption->setAttributes(['coupon_id' => $coupon->id, 'user_id' => $this->_user->id, 'invoice_id' => $invoice->id, 'coupon_code' => $code, 'discount_amount' => 10, 'redeemed_at' => time()], false);
            $this->assertTrue($redemption->save(false));
            $payment = $this->payment($invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED, null, 100.00);
            if ($kept <= 0) {
                $payment->status = Transaction::STATUS_REFUNDED;
                $this->assertTrue($payment->save(false));
            }
            $service->closeAsRefunded(Invoice::findOne($invoice->id), (int) $payment->id);

            return \common\models\CouponRedemption::find()->where(['invoice_id' => $invoice->id])->exists();
        };

        $this->assertTrue($redeemed(100.00), 'money kept: the discount was used');
        $this->assertFalse($redeemed(0.00), 'all refunded: the use is freed');
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

    /** A newer invoice holding $source's balance on an arrears line. */
    private function carry(Invoice $source): Invoice
    {
        $target = $this->invoice(500.00);
        $line = new InvoiceItem();
        $line->setAttributes(['invoice_id' => $target->id, 'item_type' => InvoiceItem::TYPE_ARREARS, 'source_invoice_id' => $source->id, 'category' => 'Arrears', 'description' => 'Carried', 'quantity' => 1, 'unit_price' => 700, 'total_price' => 700], false);
        $this->assertTrue($line->save(false));

        return $target;
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
