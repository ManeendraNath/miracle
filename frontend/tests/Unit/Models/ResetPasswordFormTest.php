<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Models;

use Codeception\Test\Unit;
use common\fixtures\UserFixture;
use common\components\LoginThrottle;
use frontend\models\ChangePasswordForm;
use frontend\models\ResetPasswordForm;
use frontend\tests\Support\UnitTester;
use yii\base\InvalidArgumentException;

final class ResetPasswordFormTest extends Unit
{
    protected UnitTester $tester;
    public function _before(): void
    {
        $this->tester->haveFixtures(
            [
                'user' => [
                    'class' => UserFixture::class,
                    'dataFile' => codecept_data_dir() . 'user.php',
                ],
            ],
        );
    }

    public function testResetWrongToken(): void
    {
        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function (): void {
                new ResetPasswordForm('');
            },
        );

        $this->tester->expectThrowable(
            InvalidArgumentException::class,
            static function (): void {
                new ResetPasswordForm('notexistingtoken_1391882543');
            },
        );
    }

    public function testResetCorrectToken(): void
    {
        $user = $this->tester->grabFixture('user', 0);

        $form = new ResetPasswordForm($user['password_reset_token']);

        verify($form->resetPassword())
            ->notEmpty();
    }

    public function testResetClearsTheLoginLock(): void
    {
        $user = $this->tester->grabFixture('user', 0);
        foreach (range(1, 5) as $attempt) {
            LoginThrottle::fail('frontend', $user['username']);
        }
        verify(LoginThrottle::isLocked('frontend', $user['username']))->true();

        $form = new ResetPasswordForm($user['password_reset_token']);
        $form->password = 'NewPassword123';
        verify($form->resetPassword())->true();

        verify(LoginThrottle::isLocked('frontend', $user['username']))->false();
    }

    public function testWrongCurrentPasswordsDoNotLockTheLoginPage(): void
    {
        $fixture = $this->tester->grabFixture('user', 0);
        $user = \common\models\User::findOne(['username' => $fixture['username']]);
        LoginThrottle::clear('frontend', $user->username);
        LoginThrottle::clear('frontend', 'user#' . $user->id);
        foreach (range(1, 6) as $attempt) {
            $form = new ChangePasswordForm($user, ['currentPassword' => 'wrong', 'newPassword' => 'NewPassword123', 'confirmPassword' => 'NewPassword123']);
            verify($form->changePassword())->false();
        }

        verify($form->getFirstError('currentPassword'))->stringContainsString('Too many failed attempts');
        verify(LoginThrottle::isLocked('frontend', $user->username))->false();
        LoginThrottle::clear('frontend', 'user#' . $user->id);
    }
}
