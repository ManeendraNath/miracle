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

    public function pendingRazorpayOrderCannotBeRejected(FunctionalTester $I): void
    {
        $order = $this->transaction($I, $this->invoice($I, Invoice::STATUS_UNPAID), Transaction::GATEWAY_RAZORPAY, Transaction::STATUS_PENDING);
        $this->post($I, 'reject', $order);
        $I->seeRecord(Transaction::class, ['id' => $order, 'status' => Transaction::STATUS_PENDING]);
        $I->amOnRoute('transaction/view', ['id' => $order]);
        $I->see('Razorpay orders are settled automatically and cannot be rejected manually.');
    }

    public function paymentOnACarriedForwardInvoiceCannotBeRefunded(FunctionalTester $I): void
    {
        $payment = $this->transaction($I, $this->invoice($I, Invoice::STATUS_CARRIED_FORWARD), Transaction::GATEWAY_MANUAL, Transaction::STATUS_CAPTURED);
        $this->post($I, 'refund', $payment);
        $I->seeRecord(Transaction::class, ['id' => $payment, 'status' => Transaction::STATUS_CAPTURED]);
        $I->amOnRoute('transaction/view', ['id' => $payment]);
        $I->see('so its payments cannot be refunded here.');
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
    private function post(FunctionalTester $I, string $action, int $id): void
    {
        $I->amOnRoute('transaction/view', ['id' => $id]);
        $I->sendAjaxPostRequest('/transaction/' . $action . '?id=' . $id, [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
        ]);
        $I->dontSee('Unable to verify your data submission');
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
