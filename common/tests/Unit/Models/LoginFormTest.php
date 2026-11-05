<?php

declare(strict_types=1);

namespace common\tests\Unit\Models;

use Codeception\Test\Unit;
use common\components\LoginThrottle;
use common\fixtures\UserFixture;
use common\models\LoginForm;
use common\tests\Support\UnitTester;
use Yii;

final class LoginFormTest extends Unit
{
    protected UnitTester $tester;
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testLoginNoUser(): void
    {
        $model = new LoginForm(
            [
                'username' => 'not_existing_username',
                'password' => 'not_existing_password',
            ],
        );

        verify($model->login())
            ->false();
        verify(Yii::$app->user->isGuest)
            ->true();
    }

    public function testOtherSpellingsOfAUsernameShareOneFailureCount(): void
    {
        LoginThrottle::clear('frontend', 'bayer.hudson');
        foreach (['bayer.hudson', 'bäyer.hudson', 'BAYER.HUDSON', 'báyer.hudson', 'bàyer.hudson'] as $spelling) {
            (new LoginForm(['username' => $spelling, 'password' => 'wrong_password']))->login();
        }

        $this->assertTrue(LoginThrottle::isLocked('frontend', 'bayer.hudson'));
        LoginThrottle::clear('frontend', 'bayer.hudson');
    }

    public function testLoginWrongPassword(): void
    {
        $model = new LoginForm(
            [
                'username' => 'bayer.hudson',
                'password' => 'wrong_password',
            ],
        );

        verify($model->login())
            ->false();
        verify($model->errors)
            ->arrayHasKey('password');
        verify(Yii::$app->user->isGuest)
            ->true();
    }

    public function testLoginCorrect(): void
    {
        $model = new LoginForm(
            [
                'username' => 'bayer.hudson',
                'password' => 'password_0',
            ],
        );

        verify($model->login())
            ->true();
        verify($model->errors)
            ->arrayHasNotKey('password');
        verify(Yii::$app->user->isGuest)
            ->false();
    }
}
