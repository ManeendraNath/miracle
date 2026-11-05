<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Models;

use Codeception\Test\Unit;
use common\fixtures\UserFixture;
use frontend\models\ResendVerificationEmailForm;
use frontend\tests\Support\UnitTester;
use Yii;
use yii\mail\MessageInterface;

final class ResendVerificationEmailFormTest extends Unit
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

    public function testWrongEmailAddress(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'aaa@bbb.cc', 'verifyCode' => 'testme'];

        // Unknown or already-verified emails validate fine (no account enumeration)
        // but no email is sent.
        verify($model->validate())
            ->true();
        verify($model->sendEmail(Yii::$app->mailer, Yii::$app->params['supportEmail'], Yii::$app->name))
            ->false();
        $this->tester->dontSeeEmailIsSent();
    }

    public function testEmptyEmailAddress(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => '', 'verifyCode' => 'testme'];

        verify($model->validate())
            ->false();
        verify($model->hasErrors())
            ->true();
        verify($model->getFirstError('email'))
            ->equals('Email cannot be blank.');
    }

    public function testResendToActiveUser(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test2@mail.com', 'verifyCode' => 'testme'];

        // Unknown or already-verified emails validate fine (no account enumeration)
        // but no email is sent.
        verify($model->validate())
            ->true();
        verify($model->sendEmail(Yii::$app->mailer, Yii::$app->params['supportEmail'], Yii::$app->name))
            ->false();
        $this->tester->dontSeeEmailIsSent();
    }

    public function testActivatingAnAccountClearsItsVerificationToken(): void
    {
        $user = \common\models\User::findOne(['email' => 'test@mail.com']);
        $user->status = \common\models\User::getStatusActive();
        $this->assertTrue($user->save(false));
        $this->assertNull(\common\models\User::findOne($user->id)->verification_token);
    }

    public function testAccountStaffSetInactiveCannotReactivateItself(): void
    {
        \common\models\User::updateAll(['verification_token' => null], ['email' => 'test@mail.com']);
        $model = new ResendVerificationEmailForm();
        $model->attributes = ['email' => 'test@mail.com', 'verifyCode' => 'testme'];

        verify($model->sendEmail(Yii::$app->mailer, Yii::$app->params['supportEmail'], Yii::$app->name))->false();
    }

    public function testSuccessfullyResend(): void
    {
        $model = new ResendVerificationEmailForm();

        $model->attributes = ['email' => 'test@mail.com', 'verifyCode' => 'testme'];

        verify($model->validate())
            ->true();
        verify($model->hasErrors())
            ->false();
        verify($model->sendEmail(Yii::$app->mailer, Yii::$app->params['supportEmail'], Yii::$app->name))
            ->true();

        $this->tester->seeEmailIsSent();
        $mail = $this->tester->grabLastSentEmail();

        verify($mail)->instanceOf(MessageInterface::class);
        verify($mail->getTo())
            ->arrayHasKey('test@mail.com');
        verify($mail->getFrom())
            ->arrayHasKey(Yii::$app->params['supportEmail']);
        verify($mail->getSubject())
            ->equals('Account registration at ' . Yii::$app->name);
        // Resending issues a fresh token (old links may have expired).
        $token = (string) \common\models\User::findOne(['email' => 'test@mail.com'])->verification_token;
        verify($token)->notEquals('4ch0qbfhvWwkcuWqjN8SWRq72SOw1KYT_9999999999');
        verify($mail->toString())
            ->stringContainsString($token);
    }
}
