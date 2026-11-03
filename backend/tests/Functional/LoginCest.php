<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use Yii;

/**
 * Admin panel login: only an active Superadmin reaches the dashboard.
 */
final class LoginCest
{
    private const PASSWORD = 'AdminTestPassword1';

    private function haveAdmin(FunctionalTester $I, string $username, string $role, int $status): void
    {
        $I->haveRecord(Admin::class, [
            'username' => $username,
            'email' => $username . '@example.test',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD),
            'role' => $role,
            'status' => $status,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function login(FunctionalTester $I, string $username, string $password): void
    {
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => $username,
            'LoginForm[password]' => $password,
        ]);
    }

    public function superadminReachesDashboard(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'test-super', Admin::getRoleSuperAdmin(), Admin::getStatusActive());
        $this->login($I, 'test-super', self::PASSWORD);
        $I->see('Miracle Corporate Control Center');
    }

    public function wrongPasswordIsRejected(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'test-super2', Admin::getRoleSuperAdmin(), Admin::getStatusActive());
        $this->login($I, 'test-super2', 'wrong-password');
        $I->see('Incorrect username or password.');
        $I->dontSee('Miracle Corporate Control Center');
    }

    public function managerUsesPanelWithoutBillingOrAdminPages(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'test-manager', Admin::getRoleManager(), Admin::getStatusActive());
        $this->login($I, 'test-manager', self::PASSWORD);
        $I->see('Miracle Corporate Control Center');
        $I->dontSee('Total Revenue Settled');
        $I->amOnRoute('invoice/index');
        $I->seeResponseCodeIs(403);
        $I->amOnRoute('admin/index');
        $I->seeResponseCodeIs(403);
        $I->amOnRoute('admin/account');
        $I->seeResponseCodeIs(200);
    }

    public function accountChangesNeedTheCurrentPassword(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'test-account', Admin::getRoleManager(), Admin::getStatusActive());
        $this->login($I, 'test-account', self::PASSWORD);
        $I->amOnRoute('admin/account');
        $I->submitForm('#account-form', ['Admin[email]' => 'taken-over@example.test', 'current_password' => 'wrong-password']);
        $I->see('Enter your current password to save changes.');
        $I->seeRecord(Admin::class, ['username' => 'test-account', 'email' => 'test-account@example.test']);

        $I->submitForm('#account-form', ['Admin[email]' => 'renamed@example.test', 'current_password' => self::PASSWORD]);
        $I->see('Your account has been updated.');
        $I->seeRecord(Admin::class, ['username' => 'test-account', 'email' => 'renamed@example.test']);
    }
}
