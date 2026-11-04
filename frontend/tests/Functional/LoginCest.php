<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use frontend\tests\Support\FunctionalTester;

final class LoginCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'login_data.php',
            ],
        ];
    }

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
    }

    protected function formParams(string $login, string $password): array
    {
        return [
            'LoginForm[username]' => $login,
            'LoginForm[password]' => $password,
        ];
    }

    public function postedArrayIsAValidationErrorNotA500(FunctionalTester $I): void
    {
        $I->sendAjaxPostRequest('/site/login', [
            \Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'LoginForm' => ['username' => ['x'], 'password' => ['y']],
        ]);
        $I->seeResponseCodeIs(200);
        $I->sendAjaxPostRequest('/site/login', [
            \Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
            'LoginForm' => 'x',
        ]);
        $I->seeResponseCodeIs(200);
    }

    public function passwordResetNeedsTheCaptcha(FunctionalTester $I): void
    {
        $I->amOnRoute('site/request-password-reset');
        $I->submitForm('#request-password-reset-form', ['PasswordResetRequestForm[email]' => 'test@mail.com', 'PasswordResetRequestForm[verifyCode]' => 'wrong']);
        $I->seeValidationError('The verification code is incorrect.');
    }

    public function checkEmpty(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('', ''));
        $I->seeValidationError('Username cannot be blank.');
        $I->seeValidationError('Password cannot be blank.');
    }

    public function checkWrongPassword(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('admin', 'wrong'));
        $I->seeValidationError('Incorrect username or password.');
    }

    public function checkInactiveAccount(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('test.test', 'Test1234'));
        $I->seeValidationError('Incorrect username or password');
    }

    public function checkValidLogin(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('erau', 'password_0'));
        $I->see('Sign Out (erau)');
        $I->dontSeeLink('Login');
        $I->dontSeeLink('Signup');
    }

    public function newDeviceSendsOneEmailButFirstEverLoginDoesNot(FunctionalTester $I): void
    {
        // First sign-in ever: nothing to compare against, so no alert.
        $I->submitForm('#login-form', $this->formParams('erau', 'password_0'));
        $I->see('Sign Out (erau)');
        $I->dontSeeEmailIsSent();

        // A browser without the device cookie, for an account that has signed in before: alert.
        $I->resetCookie('_known-accounts');
        $this->signInAgain($I);
        $I->seeEmailIsSent(1);
        $email = $I->grabLastSentEmail();
        $I->assertSame('New sign-in to your ' . \Yii::$app->params['senderName'] . ' account', $email->getSubject());
        $I->assertArrayHasKey('sfriesen@jenkins.info', $email->getTo());

        // Same browser again: known, no second alert.
        $this->signInAgain($I);
        $I->seeEmailIsSent(1);
    }

    public function signOutOtherDevicesRotatesKeyButKeepsThisBrowser(FunctionalTester $I): void
    {
        $I->submitForm('#login-form', $this->formParams('erau', 'password_0'));
        $I->amOnRoute('security/index');
        $I->see('Sign Out Other Devices');

        $I->sendAjaxPostRequest(\yii\helpers\Url::to(['/security/sign-out-other-devices']), [
            '_csrf-frontend' => $I->grabAttributeFrom('meta[name=csrf-token]', 'content'),
        ]);

        // Every other session and remember-me cookie carries the old key, which no longer validates.
        $user = \common\models\User::findOne(['username' => 'erau']);
        $I->assertFalse($user->validateAuthKey('tUu1qHcde0diwUol3xeI-18MuHkkprQI'));
        $I->amOnRoute('security/index');
        $I->see('Sign Out (erau)');
        $I->see('You have been signed out of all other devices.');
    }

    private function signInAgain(FunctionalTester $I): void
    {
        $I->click('Sign Out (erau)');
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', $this->formParams('erau', 'password_0'));
        $I->see('Sign Out (erau)');
    }

    public function describesCommonDevices(FunctionalTester $I): void
    {
        $describe = [\common\services\UserLoginHistoryService::class, 'describeDevice'];
        $I->assertSame('Chrome on Windows', $describe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'));
        $I->assertSame('Edge on Windows', $describe('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0'));
        $I->assertSame('Safari on iPhone', $describe('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'));
        $I->assertSame('Chrome on Android', $describe('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Mobile Safari/537.36'));
        $I->assertSame('Unknown device', $describe(''));
    }
}
