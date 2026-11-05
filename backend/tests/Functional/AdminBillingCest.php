<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use common\models\User;
use Yii;

/** Admin billing screens and the payment guards behind their buttons. */
final class AdminBillingCest
{
    private const PASSWORD = 'AdminTestPassword1';

    public function _before(FunctionalTester $I): void
    {
        $I->haveRecord(Admin::class, ['username' => 'billing-admin', 'email' => 'billing-admin@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => Admin::getRoleAdmin(), 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'billing-admin', 'LoginForm[password]' => self::PASSWORD]);
    }

    public function billingProfileAndRenewalListsRenderAsGrids(FunctionalTester $I): void
    {
        $I->amOnRoute('customer-billing-profile/index', ['CustomerBillingProfileSearch' => ['legal_name' => 'no-such-profile']]);
        $I->seeResponseCodeIs(200);
        $I->see('No billing profiles found.');
        $I->amOnRoute('renewal-monitoring/index');
        $I->seeResponseCodeIs(200);
        $I->see('Service Expiries');
    }

    public function serviceFormsShowTheNextRenewalPrice(FunctionalTester $I): void
    {
        foreach (['domains/create', 'hosting/create', 'maintenance/create'] as $route) {
            $I->amOnRoute($route);
            $I->seeResponseCodeIs(200);
            $I->see('Next Renewal Price (one-time)');
        }
    }

    public function arrayInASearchFilterDoesNotBreakTheList(FunctionalTester $I): void
    {
        $I->amOnPage('/invoice/index?InvoiceSearch[invoice_number][]=x&InvoiceSearch[status][]=y');
        $I->seeResponseCodeIs(200);
        $I->amOnPage('/transaction/index?TransactionSearch[gateway_payment_id][]=x');
        $I->seeResponseCodeIs(200);
    }

    public function pendingRazorpayOrderCannotBeRejected(FunctionalTester $I): void
    {
        $order = $this->transaction($I, $this->invoice($I, Invoice::STATUS_UNPAID), Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_PENDING);
        $this->post($I, 'reject', $order);
        $I->seeRecord(Transaction::class, ['id' => $order, 'status' => Transaction::STATUS_PENDING]);
        $I->amOnRoute('transaction/view', ['id' => $order]);
        $I->see('Razorpay orders are settled automatically and cannot be rejected manually.');
    }

    public function rejectedProofFreesItsReferenceForAResubmit(FunctionalTester $I): void
    {
        $proof = $this->transaction($I, $this->invoice($I, Invoice::STATUS_UNPAID), Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        $utr = 'UTR' . $proof;
        Transaction::updateAll(['gateway_payment_id' => $utr], ['id' => $proof]);
        $this->post($I, 'reject', $proof);
        $I->seeRecord(Transaction::class, ['id' => $proof, 'status' => Transaction::STATUS_FAILED, 'gateway_payment_id' => 'REJECTED-' . $proof . '-' . $utr]);
        $I->dontSeeRecord(Transaction::class, ['gateway_payment_id' => $utr]);
    }

    public function paymentOnACarriedForwardInvoiceCannotBeRefundedWhileTheBalanceIsOpen(FunctionalTester $I): void
    {
        $source = $this->invoice($I, Invoice::STATUS_CARRIED_FORWARD);
        $payment = $this->transaction($I, $source, Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED);
        $newer = $this->invoice($I, Invoice::STATUS_UNPAID);
        $I->haveRecord(InvoiceItem::class, ['invoice_id' => $newer, 'item_type' => InvoiceItem::TYPE_ARREARS, 'source_invoice_id' => $source, 'category' => 'Arrears', 'description' => 'Carried', 'quantity' => 1, 'unit_price' => 700, 'total_price' => 700]);

        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED]);
        $I->see('cannot be refunded until that invoice is paid or written off');

        Invoice::updateAll(['status' => Invoice::STATUS_PAID], ['id' => $newer]);
        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_REFUNDED]);
        $I->seeRecord(Invoice::class, ['id' => $source, 'status' => Invoice::STATUS_CARRIED_FORWARD]);
    }

    public function refundAndCloseSendsItsChoiceThroughTheConfirmDialog(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_PAID), Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        $I->amOnRoute('transaction/view', ['id' => $payment]);
        // yii.js submits a confirmed form itself, which drops a clicked button's name and value.
        $I->seeElement('button[data-params*="close_invoice"]');
        $I->dontSeeElement('button[name="close_invoice"]');
    }

    public function aPartPaidInvoiceCanBeWrittenOffKeepingTheMoney(FunctionalTester $I): void
    {
        $invoice = $this->invoice($I, Invoice::STATUS_PARTIALLY_PAID);
        $payment = $this->transaction($I, $invoice, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        Transaction::updateAll(['amount' => 300], ['id' => $payment]);
        $I->amOnRoute('invoice/view', ['number' => Invoice::findOne($invoice)->invoice_number]);
        $I->see('Write Off Balance');
        $I->sendAjaxPostRequest('/invoice/close?id=' . $invoice, [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->seeRecord(Invoice::class, ['id' => $invoice, 'status' => Invoice::STATUS_REFUNDED]);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED]);
    }

    public function aManagerCannotChangeACustomersLogin(FunctionalTester $I): void
    {
        $suffix = bin2hex(random_bytes(4));
        $customer = $I->haveRecord(User::class, ['username' => 'login-customer-' . $suffix, 'email' => 'login-' . $suffix . '@example.test', 'auth_key' => 'x', 'password_hash' => 'original-hash', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->haveRecord(Admin::class, ['username' => 'billing-manager', 'email' => 'billing-manager@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => 'Manager', 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('dashboard/index');
        $I->sendAjaxPostRequest('/auth/logout', [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'billing-manager', 'LoginForm[password]' => self::PASSWORD]);

        $I->amOnRoute('user/update', ['id' => $customer]);
        $I->seeResponseCodeIs(200);
        $I->sendAjaxPostRequest('/user/update?id=' . $customer, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'User[username]' => 'login-customer-' . $suffix,
            'User[email]' => 'attacker-' . $suffix . '@example.test',
            'User[password]' => 'NewPassword123',
            'User[status]' => (string) User::getStatusActive(),
        ]);
        $I->seeRecord(User::class, ['id' => $customer, 'email' => 'login-' . $suffix . '@example.test', 'password_hash' => 'original-hash']);
    }

    public function partialRefundCanCloseTheInvoiceKeepingTheRest(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_PAID), Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        $this->post($I, 'refund', $payment, ['refund_amount' => '800', 'close_invoice' => '1', 'refunded_before' => '0.00']);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED, 'refunded_amount' => 800]);
        $I->seeRecord(Invoice::class, ['id' => Transaction::findOne($payment)->invoice_id, 'status' => Invoice::STATUS_REFUNDED]);

        // The same form submitted again (second tab) is refused instead of refunding twice.
        $this->post($I, 'refund', $payment, ['refund_amount' => '100', 'refunded_before' => '0.00']);
        $I->see('This payment was refunded meanwhile');
        $I->seeRecord(Transaction::class, ['id' => $payment, 'refunded_amount' => 800]);
    }

    public function customerTypedUnappliedTextStillCountsAsPaid(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_UNPAID);
        $proof = $this->transaction($I, $invoiceId, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        Transaction::updateAll(['user_payment_notes' => 'UNAPPLIED: sent from HDFC', 'gateway_payment_id' => 'UTR-NOTE-' . $proof], ['id' => $proof]);
        $this->post($I, 'approve', $proof);
        $I->seeRecord(Invoice::class, ['id' => $invoiceId, 'status' => Invoice::STATUS_PAID]);
    }

    public function unappliedPaymentCanBeAppliedToAnotherInvoice(FunctionalTester $I): void
    {
        $paidId = $this->invoice($I, Invoice::STATUS_PAID);
        $userId = (int) Invoice::findOne($paidId)->user_id;
        $targetId = (int) $I->haveRecord(Invoice::class, ['user_id' => $userId, 'invoice_number' => 'TEST-ADM-T' . $paidId, 'client_name' => 'Billing Test', 'subtotal_amount' => 1000, 'discount_amount' => 0, 'total_payable' => 1000, 'status' => Invoice::STATUS_UNPAID, 'created_at' => time(), 'updated_at' => time()]);
        $payment = $this->transaction($I, $paidId, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_CAPTURED);
        Transaction::updateAll(['is_unapplied' => 1], ['id' => $payment]);

        $this->post($I, 'apply', $payment, ['target_invoice' => 'TEST-ADM-T' . $paidId]);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'invoice_id' => $targetId, 'is_unapplied' => 0]);
        $I->seeRecord(Invoice::class, ['id' => $targetId, 'status' => Invoice::STATUS_PAID]);
    }

    public function unappliedPaymentOnACarriedForwardInvoiceCanBeRefunded(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_CARRIED_FORWARD), Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED);
        Transaction::updateAll(['is_unapplied' => 1, 'user_payment_notes' => Transaction::UNAPPLIED_NOTE_PREFIX . ' invoice was Carried_Forward when paid.'], ['id' => $payment]);
        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_REFUNDED]);
    }

    public function partialRefundKeepsThePaymentAndReopensTheBalance(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_PAID), Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        $this->post($I, 'refund', $payment, ['refund_amount' => '400']);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED, 'refunded_amount' => 400]);
        $I->seeRecord(Invoice::class, ['id' => Transaction::findOne($payment)->invoice_id, 'status' => Invoice::STATUS_PARTIALLY_PAID]);

        $this->post($I, 'refund', $payment, ['refund_amount' => '700']);
        $I->see('Enter a refund amount between ₹0.01 and ₹600.00.');
        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_REFUNDED, 'refunded_amount' => 1000]);
    }

    public function refundAndCloseIsRefusedBeforeAnythingIsRefunded(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_PAID);
        $payment = $this->transaction($I, $invoiceId, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_CAPTURED);
        $this->transaction($I, $invoiceId, Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED);
        $this->post($I, 'refund', $payment, ['close_invoice' => '1']);
        $I->see('Other payments on this invoice are still kept');
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED, 'refunded_amount' => 0]);
    }

    public function staffSettingTheExpiryDateSetsTheRenewalDay(FunctionalTester $I): void
    {
        $suffix = bin2hex(random_bytes(4));
        $userId = $I->haveRecord(User::class, ['username' => 'anchor-customer-' . $suffix, 'email' => 'anchor-' . $suffix . '@example.test', 'auth_key' => 'x', 'password_hash' => 'x', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $hostingId = (int) $I->haveRecord(\common\models\Hosting::class, ['user_id' => $userId, 'primary_domain' => 'anchor-' . $suffix . '.test', 'current_expiry_date' => '2026-02-28', 'renewal_anchor_day' => 31, 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);

        $I->amOnRoute('hosting/update', ['id' => $hostingId]);
        $I->sendAjaxPostRequest('/hosting/update?id=' . $hostingId, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'Hosting[current_expiry_date]' => '2026-01-30',
        ]);

        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'current_expiry_date' => '2026-01-30', 'renewal_anchor_day' => 30]);
    }

    public function expiryCannotChangeWhileARenewalInvoiceIsOpen(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_UNPAID);
        $userId = (int) Invoice::findOne($invoiceId)->user_id;
        $hostingId = (int) $I->haveRecord(\common\models\Hosting::class, ['user_id' => $userId, 'primary_domain' => 'open-' . $invoiceId . '.test', 'current_expiry_date' => '2026-12-01', 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $runId = (int) $I->haveRecord(\common\models\BillingRun::class, ['run_number' => 'TEST-' . bin2hex(random_bytes(4)), 'status' => 'Completed', 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()]);
        $I->haveRecord(\common\models\BillingEvent::class, ['billing_run_id' => $runId, 'user_id' => $userId, 'item_type' => \common\models\InvoiceItem::TYPE_HOSTING, 'item_id' => $hostingId, 'description' => 'Hosting', 'period_start' => '2026-12-02', 'period_end' => '2027-12-01', 'previous_amount' => 0, 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => '2026-12-01', 'invoice_id' => $invoiceId, 'status' => \common\models\BillingEvent::STATUS_INVOICED, 'created_at' => time(), 'updated_at' => time()]);

        $I->amOnRoute('hosting/update', ['id' => $hostingId]);
        $I->sendAjaxPostRequest('/hosting/update?id=' . $hostingId, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'Hosting[current_expiry_date]' => '2026-12-05',
        ]);
        $I->see('must be paid or cancelled before the expiry date or customer can change');
        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'current_expiry_date' => '2026-12-01']);

        // Once that balance was carried onto a newer invoice the change is allowed.
        Invoice::updateAll(['status' => Invoice::STATUS_CARRIED_FORWARD], ['id' => $invoiceId]);
        $I->amOnRoute('hosting/update', ['id' => $hostingId]);
        $I->sendAjaxPostRequest('/hosting/update?id=' . $hostingId, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'Hosting[current_expiry_date]' => '2026-12-05',
        ]);
        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'current_expiry_date' => '2026-12-05']);

        // The customer can't: only the one who owes the carried balance can pay it.
        $other = (int) Invoice::findOne($this->invoice($I, Invoice::STATUS_UNPAID))->user_id;
        $I->amOnRoute('hosting/update', ['id' => $hostingId]);
        $I->sendAjaxPostRequest('/hosting/update?id=' . $hostingId, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'Hosting[user_id]' => $other,
        ]);
        $I->see('It must be paid before the customer can change.');
        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'user_id' => $userId]);
    }

    public function aRejectedProofEmailsTheCustomer(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_UNPAID);
        $proof = $this->transaction($I, $invoiceId, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        Transaction::updateAll(['gateway_payment_id' => 'UTR-REJECT-' . $proof, 'payment_receipt_file' => 'proof.png'], ['id' => $proof]);

        $this->post($I, 'reject', $proof);

        $I->seeEmailIsSent(1);
        $mail = quoted_printable_decode((string) $I->grabLastSentEmail()->toString());
        $I->assertStringContainsString('UTR-REJECT-' . $proof, $mail, 'the reference as they submitted it');
        $I->assertStringNotContainsString('REJECTED-', $mail);
    }

    public function dashboardShowsUnappliedMoney(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_PAID);
        $payment = $this->transaction($I, $invoiceId, Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_CAPTURED);
        Transaction::updateAll(['is_unapplied' => 1, 'refunded_amount' => 400], ['id' => $payment]);

        $I->amOnRoute('dashboard/index');
        $I->see('received but unapplied');
        $I->amOnRoute('transaction/index', ['TransactionSearch' => ['is_unapplied' => 1]]);
        $I->see('Unapplied');
    }

    public function anUnpaidInvoiceCanBeWrittenOffUnlessAProofAwaitsReview(FunctionalTester $I): void
    {
        $closeFrom = static function (int $id) use ($I): void {
            $number = Invoice::findOne($id)->invoice_number;
            $I->amOnRoute('invoice/view', ['number' => $number]);
            $I->sendAjaxPostRequest('/invoice/close?id=' . $id, [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
            $I->amOnRoute('invoice/view', ['number' => $number]);
        };

        $unpaid = $this->invoice($I, Invoice::STATUS_UNPAID);
        $closeFrom($unpaid);
        $I->see('nothing more is owed on it');
        $I->seeRecord(Invoice::class, ['id' => $unpaid, 'status' => Invoice::STATUS_REFUNDED]);

        $pending = $this->invoice($I, Invoice::STATUS_UNPAID);
        $this->transaction($I, $pending, Transaction::GATEWAY_UPI_BANK, Transaction::STATUS_PENDING);
        $closeFrom($pending);
        $I->see('awaiting review');
        $I->seeRecord(Invoice::class, ['id' => $pending, 'status' => Invoice::STATUS_UNPAID]);
    }

    public function aBlankDomainStatusIsNotADisable(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_UNPAID);
        $userId = (int) Invoice::findOne($invoiceId)->user_id;
        $domainId = (int) $I->haveRecord(\common\models\Domains::class, ['user_id' => $userId, 'domain_url' => 'blank-' . $invoiceId . '.test', 'registrar' => 'x', 'registered_date' => '2020-01-01', 'current_expiry_date' => '2026-12-01', 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $runId = (int) $I->haveRecord(\common\models\BillingRun::class, ['run_number' => 'TEST-' . bin2hex(random_bytes(4)), 'status' => 'Completed', 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()]);
        $eventId = (int) $I->haveRecord(\common\models\BillingEvent::class, ['billing_run_id' => $runId, 'user_id' => $userId, 'item_type' => \common\models\InvoiceItem::TYPE_DOMAIN, 'item_id' => $domainId, 'description' => 'Domain', 'period_start' => '2026-12-02', 'period_end' => '2027-12-01', 'previous_amount' => 0, 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => '2026-12-01', 'status' => \common\models\BillingEvent::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()]);

        $I->amOnRoute('domains/update', ['id' => $domainId]);
        $I->sendAjaxPostRequest('/domains/update?id=' . $domainId, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'Domains[status]' => '',
        ]);
        $I->seeRecord(\common\models\BillingEvent::class, ['id' => $eventId, 'status' => \common\models\BillingEvent::STATUS_PENDING]);
    }

    public function expiryCannotChangeWhileABillingRunIsInProgress(FunctionalTester $I): void
    {
        $userId = (int) Invoice::findOne($this->invoice($I, Invoice::STATUS_UNPAID))->user_id;
        $hostingId = (int) $I->haveRecord(\common\models\Hosting::class, ['user_id' => $userId, 'primary_domain' => 'run-' . $userId . '.test', 'current_expiry_date' => '2026-12-01', 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $save = static function () use ($I, $hostingId): void {
            $I->amOnRoute('hosting/update', ['id' => $hostingId]);
            $I->sendAjaxPostRequest('/hosting/update?id=' . $hostingId, [
                Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
                'Hosting[current_expiry_date]' => '2026-12-05',
            ]);
        };

        // The cron's own database connection holds the lock.
        $db = Yii::$app->db;
        $cron = new \yii\mutex\MysqlMutex(['db' => new \yii\db\Connection(['dsn' => $db->dsn, 'username' => $db->username, 'password' => $db->password]), 'autoRelease' => false]);
        $I->assertTrue($cron->acquire(\common\services\billing\AutoBillingService::LOCK_NAME));
        try {
            $save();
            $I->see('The billing run is in progress.');
            $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'current_expiry_date' => '2026-12-01']);
        } finally {
            $cron->release(\common\services\billing\AutoBillingService::LOCK_NAME);
        }

        // Saved once the run has finished, and the lock is given back for the next run.
        $save();
        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'current_expiry_date' => '2026-12-05']);
        $I->assertTrue($cron->acquire(\common\services\billing\AutoBillingService::LOCK_NAME));
        $cron->release(\common\services\billing\AutoBillingService::LOCK_NAME);
    }

    public function disablingAServiceCancelsItsUnpaidRenewalInvoice(FunctionalTester $I): void
    {
        $invoiceId = $this->invoice($I, Invoice::STATUS_UNPAID);
        $userId = (int) Invoice::findOne($invoiceId)->user_id;
        $hostingId = (int) $I->haveRecord(\common\models\Hosting::class, ['user_id' => $userId, 'primary_domain' => 'disable-' . $invoiceId . '.test', 'current_expiry_date' => '2026-12-01', 'status' => 1, 'created_at' => time(), 'updated_at' => time()]);
        $I->haveRecord(\common\models\InvoiceItem::class, ['invoice_id' => $invoiceId, 'item_type' => \common\models\InvoiceItem::TYPE_HOSTING, 'item_id' => $hostingId, 'category' => 'Hosting', 'description' => 'Renewal', 'quantity' => 1, 'unit_price' => 1000, 'total_price' => 1000]);
        $runId = (int) $I->haveRecord(\common\models\BillingRun::class, ['run_number' => 'TEST-' . bin2hex(random_bytes(4)), 'status' => 'Completed', 'started_at' => time(), 'created_at' => time(), 'updated_at' => time()]);
        $eventId = (int) $I->haveRecord(\common\models\BillingEvent::class, ['billing_run_id' => $runId, 'user_id' => $userId, 'item_type' => \common\models\InvoiceItem::TYPE_HOSTING, 'item_id' => $hostingId, 'description' => 'Hosting', 'period_start' => '2026-12-02', 'period_end' => '2027-12-01', 'previous_amount' => 0, 'increase_percent' => 0, 'calculated_amount' => 1000, 'amount' => 1000, 'is_manual_amount' => 0, 'due_date' => '2026-12-01', 'invoice_id' => $invoiceId, 'status' => \common\models\BillingEvent::STATUS_INVOICED, 'created_at' => time(), 'updated_at' => time()]);

        $I->amOnRoute('hosting/view', ['id' => $hostingId]);
        $I->sendAjaxPostRequest('/hosting/toggle-status?id=' . $hostingId, [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);

        $I->seeRecord(\common\models\Hosting::class, ['id' => $hostingId, 'status' => 0]);
        $I->seeRecord(Invoice::class, ['id' => $invoiceId, 'status' => Invoice::STATUS_CANCELLED]);
        $I->seeRecord(\common\models\BillingEvent::class, ['id' => $eventId, 'status' => \common\models\BillingEvent::STATUS_CANCELLED]);
    }

    public function razorpayTestPageOpensAndRejectsAForgedCheckout(FunctionalTester $I): void
    {
        $I->amOnRoute('razorpay-test/index');
        $I->seeResponseCodeIs(200);
        $I->see('No invoice, customer or transaction is created or changed.');
        $I->sendAjaxPostRequest('/razorpay-test/verify', [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'razorpay_order_id' => 'order_forged',
            'razorpay_payment_id' => 'pay_forged',
            'razorpay_signature' => 'forged',
        ]);
        $I->see('Signature check failed');
    }

    /** POST a transaction action the way its button does (with the page's CSRF token). */
    private function post(FunctionalTester $I, string $action, int $id, array $data = []): void
    {
        $I->amOnRoute('transaction/view', ['id' => $id]);
        $I->sendAjaxPostRequest('/transaction/' . $action . '?id=' . $id, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
        ] + $data);
        $I->dontSee('Unable to verify your data submission');
        $I->amOnRoute('transaction/view', ['id' => $id]); // the flash shows on the next page
    }

    private function invoice(FunctionalTester $I, string $status): int
    {
        $suffix = bin2hex(random_bytes(4));
        $userId = $I->haveRecord(User::class, ['username' => 'billing-customer-' . $suffix, 'email' => 'billing-' . $suffix . '@example.test', 'auth_key' => 'x', 'password_hash' => 'x', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);

        return (int) $I->haveRecord(Invoice::class, ['user_id' => $userId, 'invoice_number' => 'TEST-ADM-' . $suffix, 'client_name' => 'Billing Test', 'subtotal_amount' => 1000, 'discount_amount' => 0, 'total_payable' => 1000, 'status' => $status, 'created_at' => time(), 'updated_at' => time()]);
    }

    private function transaction(FunctionalTester $I, int $invoiceId, int $gateway, string $status): int
    {
        return (int) $I->haveRecord(Transaction::class, ['invoice_id' => $invoiceId, 'gateway_id' => $gateway, 'gateway_order_id' => 'order_adm_' . bin2hex(random_bytes(4)), 'amount' => 1000, 'status' => $status, 'paid_at' => $status === Transaction::STATUS_CAPTURED ? time() : null, 'created_at' => time(), 'updated_at' => time()]);
    }
}
