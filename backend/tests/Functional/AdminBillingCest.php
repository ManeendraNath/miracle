<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\Invoice;
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

    public function paymentOnACarriedForwardInvoiceCannotBeRefunded(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_CARRIED_FORWARD), Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED);
        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED]);
        $I->amOnRoute('transaction/view', ['id' => $payment]);
        $I->see('so its payments cannot be refunded here.');
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
