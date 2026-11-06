<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use common\models\Hosting;
use common\models\Testimonial;
use common\models\User;
use frontend\tests\Support\FunctionalTester;

/** A customer's own testimonial stays hidden until staff publish it. */
final class FeedbackCest
{
    public function _fixtures(): array
    {
        return ['user' => ['class' => UserFixture::class, 'dataFile' => codecept_data_dir() . 'login_data.php']];
    }

    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'erau', 'LoginForm[password]' => 'password_0']);
        // Something live, so the billing-details gate shows a banner instead of blocking the page.
        $I->haveRecord(Hosting::class, ['user_id' => $this->userId(), 'primary_domain' => 'feedback.test', 'start_date' => '2020-01-01', 'current_expiry_date' => '2030-01-01', 'status' => Hosting::STATUS_ACTIVE, 'created_at' => time(), 'updated_at' => time()]);
    }

    public function consentIsRequired(FunctionalTester $I): void
    {
        $I->amOnRoute('feedback/index');
        $I->submitForm('#feedback-form', ['Testimonial[client_name]' => 'Erau', 'Testimonial[quote]' => 'Great work.', 'Testimonial[consent]' => '0']);
        $I->see('Please agree so we can publish your words.');
        $I->dontSeeRecord(Testimonial::class, ['user_id' => $this->userId()]);
    }

    public function aCustomerCannotPublishTheirOwnQuoteOrSendASecond(FunctionalTester $I): void
    {
        $I->amOnRoute('feedback/index');
        $I->submitForm('#feedback-form', ['Testimonial[client_name]' => 'Erau', 'Testimonial[quote]' => 'Great work.', 'Testimonial[consent]' => '1', 'Testimonial[is_published]' => '1', 'Testimonial[sort_order]' => '-5', 'Testimonial[user_id]' => '999999']);
        $I->seeRecord(Testimonial::class, ['user_id' => $this->userId(), 'is_published' => 0, 'sort_order' => 0, 'quote' => 'Great work.']);
        $I->amOnRoute('feedback/index');
        $I->see('We review it before it appears');
        $I->dontSeeElement('#feedback-form');
        $I->amOnRoute('site/index');
        $I->dontSee('What our clients say');
    }

    private function userId(): int
    {
        return (int) User::findOne(['username' => 'erau'])->id;
    }
}
