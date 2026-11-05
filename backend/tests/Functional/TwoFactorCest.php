<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\components\Totp;
use common\models\Admin;
use Yii;
use yii\helpers\Url;

/**
 * Admin two-factor sign-in: the password alone doesn't log in an account with
 * two-factor on; only a Superadmin can turn off someone else's.
 */
final class TwoFactorCest
{
    private const PASSWORD = 'AdminTestPassword1';
    private const SECRET = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP';

    private function haveAdmin(FunctionalTester $I, string $username, string $role, ?string $secret): int
    {
        return (int) $I->haveRecord(Admin::class, [
            'username' => $username,
            'email' => $username . '@example.test',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD),
            'role' => $role,
            'status' => Admin::getStatusActive(),
            'totp_secret' => $secret,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    private function login(FunctionalTester $I, string $username): void
    {
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', [
            'LoginForm[username]' => $username,
            'LoginForm[password]' => self::PASSWORD,
            'LoginForm[rememberMe]' => '1',
        ]);
    }

    /** POST with the CSRF token of the page currently open. */
    private function post(FunctionalTester $I, array $route): void
    {
        $I->sendAjaxPostRequest(Url::to($route), [
            Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
        ]);
    }

    private function currentCode(): string
    {
        return Totp::code(self::SECRET, intdiv(time(), 30));
    }

    public function passwordAloneAsksForCode(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'tf-super', Admin::getRoleSuperAdmin(), self::SECRET);
        $this->login($I, 'tf-super');
        $I->see('Two-factor sign-in');
        $I->dontSee('Miracle Corporate Control Center');
        $I->amOnRoute('dashboard/index');
        $I->dontSee('Miracle Corporate Control Center');
    }

    public function validCodeLogsInOnceAndCannotBeReplayed(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'tf-super2', Admin::getRoleSuperAdmin(), self::SECRET);
        $code = $this->currentCode();
        $this->login($I, 'tf-super2');
        $I->submitForm('#two-factor-form', ['TwoFactorForm[code]' => $code]);
        $I->see('Miracle Corporate Control Center');
        // A secret saved before encryption existed is encrypted at the first valid code.
        /** @var Admin $admin */
        $admin = $I->grabRecord(Admin::class, ['username' => 'tf-super2']);
        $I->assertStringStartsWith('enc:', (string) $admin->totp_secret);

        $this->post($I, ['/auth/logout']);
        $this->login($I, 'tf-super2');
        $I->submitForm('#two-factor-form', ['TwoFactorForm[code]' => $code]);
        $I->see('That code is not valid.');
        $I->dontSee('Miracle Corporate Control Center');
    }

    public function wrongCodeIsRejected(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'tf-super3', Admin::getRoleSuperAdmin(), self::SECRET);
        $this->login($I, 'tf-super3');
        $wrong = str_pad((string) (((int) $this->currentCode() + 500000) % 1000000), 6, '0', STR_PAD_LEFT);
        $I->submitForm('#two-factor-form', ['TwoFactorForm[code]' => $wrong]);
        $I->see('That code is not valid.');
        $I->amOnRoute('dashboard/index');
        $I->dontSee('Miracle Corporate Control Center');
    }

    public function codePageWithoutPasswordGoesToLogin(FunctionalTester $I): void
    {
        $I->amOnRoute('auth/two-factor');
        $I->see('Your sign-in expired. Please log in again.');
        $I->seeElement('#login-form');
    }

    public function superadminResetsAnotherAdminsTwoFactor(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'tf-root', Admin::getRoleSuperAdmin(), null);
        $id = $this->haveAdmin($I, 'tf-lost', Admin::getRoleAdmin(), self::SECRET);
        $this->login($I, 'tf-root');
        $I->see('Miracle Corporate Control Center');
        $I->amOnRoute('admin/update', ['id' => $id]);
        $I->see('Turn off two-factor');
        $this->post($I, ['/admin/reset-two-factor', 'id' => $id]);
        $I->seeRecord(Admin::class, ['id' => $id, 'totp_secret' => null]);
    }

    public function managerCannotResetSomeoneElses(FunctionalTester $I): void
    {
        $this->haveAdmin($I, 'tf-manager', Admin::getRoleManager(), null);
        $id = $this->haveAdmin($I, 'tf-victim', Admin::getRoleAdmin(), self::SECRET);
        $this->login($I, 'tf-manager');
        $I->amOnRoute('admin/account');
        $this->post($I, ['/admin/reset-two-factor', 'id' => $id]);
        $I->seeResponseCodeIs(403);
        $I->seeRecord(Admin::class, ['id' => $id, 'totp_secret' => self::SECRET]);
    }

    public function staffTurnsOwnTwoFactorOnAndOff(FunctionalTester $I): void
    {
        $id = $this->haveAdmin($I, 'tf-own', Admin::getRoleManager(), null);
        $this->login($I, 'tf-own');
        $I->amOnRoute('admin/account');
        $I->click('Set up two-factor sign-in');
        $secret = str_replace(' ', '', $I->grabTextFrom('code.h5'));
        $I->fillField('#two-factor-current-password', 'wrong-password');
        $step = intdiv(time(), 30);
        $I->fillField('code', Totp::code($secret, $step));
        $I->click('Confirm and turn on');
        $I->see('Enter your current password to turn on two-factor sign-in.');
        $I->seeRecord(Admin::class, ['id' => $id, 'totp_secret' => null]);

        $I->fillField('#two-factor-current-password', self::PASSWORD);
        $I->fillField('code', '000000');
        $I->click('Confirm and turn on');
        $I->see('That code is not valid.');
        $I->seeRecord(Admin::class, ['id' => $id, 'totp_secret' => null]);

        $I->fillField('#two-factor-current-password', self::PASSWORD);
        $I->fillField('code', Totp::code($secret, $step));
        $I->click('Confirm and turn on');
        $I->see('Two-factor sign-in is on.');
        $I->dontSeeInSource($secret);
        /** @var Admin $admin */
        $admin = $I->grabRecord(Admin::class, ['id' => $id]);
        $stored = (string) $admin->totp_secret;
        $I->assertStringStartsWith('enc:', $stored, 'secret is stored encrypted');
        $I->assertStringNotContainsString($secret, $stored);

        // The code just used is spent; the next step's code is still inside the window.
        $I->fillField('code', Totp::code($secret, $step + 1));
        $I->click('Turn off');
        $I->see('Two-factor sign-in is off.');
        $I->seeRecord(Admin::class, ['id' => $id, 'totp_secret' => null]);
    }
}
