<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use common\models\CustomerBillingProfile;
use common\models\Hosting;
use common\models\User;
use frontend\tests\Support\FunctionalTester;

/** Billing details first: see ProfileGate. */
final class ProfileGateCest
{
    public function _fixtures(): array
    {
        return ['user' => ['class' => UserFixture::class, 'dataFile' => codecept_data_dir() . 'login_data.php']];
    }

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'erau', 'LoginForm[password]' => 'password_0']);
    }

    public function loginGoesToTheFormAndOnlySupportStaysOpen(FunctionalTester $I): void
    {
        $I->seeInCurrentUrl('account/profile');
        foreach (['dashboard/index', 'invoice/index', 'service/index', 'security/index'] as $route) {
            $I->amOnRoute($route);
            $I->seeInCurrentUrl('account/profile');
        }
        $I->see('Please complete your billing details to continue.');
        $I->amOnRoute('support/index');
        $I->seeInCurrentUrl('support');
        $I->see('Your billing details are incomplete.');
        $I->amOnRoute('site/index');
        $I->seeInCurrentUrl('/');
    }

    public function changePasswordStaysOpenAndPreferencesAreHidden(FunctionalTester $I): void
    {
        $I->dontSee('Notification Preferences');
        $I->amOnRoute('account/change-password');
        $I->seeInCurrentUrl('change-password');
        $I->amOnRoute('account/preferences');
        $I->seeInCurrentUrl('account/profile');
    }

    public function aTicketDoesNotUnlockThePortal(FunctionalTester $I): void
    {
        $I->haveRecord(\common\models\SupportTicket::class, ['user_id' => $this->userId(), 'ticket_number' => 'GATE-' . bin2hex(random_bytes(3)), 'subject' => 'Help', 'message' => 'Help', 'status' => \common\models\SupportTicket::STATUS_OPEN, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('dashboard/index');
        $I->seeInCurrentUrl('account/profile');
    }

    public function aCustomerWithAPaidInvoiceCanStillReachIt(FunctionalTester $I): void
    {
        $I->haveRecord(\common\models\Invoice::class, ['user_id' => $this->userId(), 'invoice_number' => 'GATE-' . bin2hex(random_bytes(3)), 'client_name' => 'Erau', 'subtotal_amount' => 100, 'discount_amount' => 0, 'total_payable' => 100, 'status' => \common\models\Invoice::STATUS_PAID, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('invoice/index');
        $I->seeInCurrentUrl('invoice');
        $I->see('Your billing details are incomplete.');
        $I->amOnRoute('dashboard/index');
        $I->seeInCurrentUrl('account/profile');
    }

    public function aCustomerWithSomethingLiveGetsABannerNotABlock(FunctionalTester $I): void
    {
        $I->haveRecord(Hosting::class, ['user_id' => $this->userId(), 'primary_domain' => 'gate.test', 'start_date' => '2020-01-01', 'current_expiry_date' => '2030-01-01', 'status' => Hosting::STATUS_ACTIVE, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('dashboard/index');
        $I->seeInCurrentUrl('dashboard');
        $I->see('Your billing details are incomplete.');
    }

    public function completeDetailsOpenEverything(FunctionalTester $I): void
    {
        $I->haveRecord(CustomerBillingProfile::class, ['user_id' => $this->userId(), 'legal_name' => 'Erau', 'address_line_1' => '1 Road', 'city' => 'Delhi', 'state' => 'Delhi', 'state_code' => '07', 'postal_code' => '110001', 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('invoice/index');
        $I->seeInCurrentUrl('invoice');
        $I->dontSee('Your billing details are incomplete.');
    }

    public function aPartialProfileStillCountsAsIncomplete(FunctionalTester $I): void
    {
        $I->haveRecord(CustomerBillingProfile::class, ['user_id' => $this->userId(), 'legal_name' => 'Erau', 'state' => 'Delhi', 'state_code' => '07', 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('invoice/index');
        $I->seeInCurrentUrl('account/profile');
    }

    private function userId(): int
    {
        return (int) User::findOne(['username' => 'erau'])->id;
    }
}
