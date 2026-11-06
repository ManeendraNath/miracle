<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;

final class ContactCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/contact');
    }

    public function checkContact(FunctionalTester $I): void
    {
        $I->see('Tell us about your project', 'h1');
    }

    public function checkContactSubmitNoData(FunctionalTester $I): void
    {
        $I->submitForm('#contact-form', []);
        $I->see('Tell us about your project', 'h1');
        $I->seeValidationError('Full Name cannot be blank');
        $I->seeValidationError('Email Address cannot be blank');
        $I->seeValidationError('Inquiry Subject cannot be blank');
        $I->seeValidationError('Message Body cannot be blank');
        $I->seeValidationError('The verification code is incorrect');
    }

    public function checkContactSubmitNotCorrectEmail(FunctionalTester $I): void
    {
        $I->submitForm(
            '#contact-form', 
            [
                'ContactForm[name]' => 'tester',
                'ContactForm[email]' => 'tester.email',
                'ContactForm[subject]' => 'test subject',
                'ContactForm[body]' => 'test content',
                'ContactForm[verifyCode]' => 'testme',
            ],
        );
        $I->seeValidationError('Email Address is not a valid email address.');
        $I->dontSeeValidationError('Full Name cannot be blank');
        $I->dontSeeValidationError('Inquiry Subject cannot be blank');
        $I->dontSeeValidationError('Message Body cannot be blank');
        $I->dontSeeValidationError('The verification code is incorrect');
    }

    public function checkContactSubmitCorrectData(FunctionalTester $I): void
    {
        $I->submitForm(
            '#contact-form', 
            [
                'ContactForm[name]' => 'tester',
                'ContactForm[email]' => 'tester@example.com',
                'ContactForm[subject]' => 'test subject',
                'ContactForm[body]' => 'test content',
                'ContactForm[verifyCode]' => 'testme',
            ],
        );
        $I->seeEmailIsSent();
        $I->see('Thank you for contacting us. We will respond shortly.');
        $I->seeRecord(\common\models\Lead::class, ['email' => 'tester@example.com', 'subject' => 'test subject', 'message' => 'test content', 'source' => \common\models\Lead::SOURCE_CONTACT_FORM, 'status' => 'Pending']);
    }
}
