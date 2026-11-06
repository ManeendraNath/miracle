<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\Testimonial;
use Yii;

/** Testimonials are Admin-only and start hidden. */
final class TestimonialCest
{
    private const PASSWORD = 'AdminTestPassword1';

    private function logInAs(FunctionalTester $I, string $role): void
    {
        $username = 'quote-' . strtolower($role);
        $I->haveRecord(Admin::class, ['username' => $username, 'email' => $username . '@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => $role, 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => $username, 'LoginForm[password]' => self::PASSWORD]);
    }

    public function adminAddsAQuoteThatStaysHidden(FunctionalTester $I): void
    {
        $this->logInAs($I, 'Admin');
        $I->amOnRoute('testimonial/create');
        $I->submitForm('form', ['Testimonial[client_name]' => 'Asha Rao', 'Testimonial[country]' => 'UK', 'Testimonial[quote]' => 'Clear and on time.']);
        $I->seeRecord(Testimonial::class, ['client_name' => 'Asha Rao', 'is_published' => 0]);
        $I->see('Hidden');
    }

    public function managerCannotOpenTheSection(FunctionalTester $I): void
    {
        $this->logInAs($I, 'Manager');
        $I->amOnRoute('testimonial/index');
        $I->seeResponseCodeIs(403);
    }
}
