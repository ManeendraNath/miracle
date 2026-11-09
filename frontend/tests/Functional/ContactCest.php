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

    public function trapFieldFilledIsKeptAsSpamWithoutEmail(FunctionalTester $I): void
    {
        $I->submitForm('#contact-form', [
            'ContactForm[name]' => 'Bot',
            'ContactForm[email]' => 'trap-bot@example.com',
            'ContactForm[subject]' => 'Hello',
            'ContactForm[body]' => 'Hello there',
            'ContactForm[verifyCode]' => 'testme',
            'mw_hp_extra' => 'https://spam.example',
        ]);
        $I->see('Thank you for contacting us.');
        $I->seeRecord(\common\models\Lead::class, ['email' => 'trap-bot@example.com', 'status' => \common\models\Lead::STATUS_SPAM]);
        $I->dontSeeEmailIsSent();
    }

    public function salesPitchIsSavedAsSpamWithoutEmail(FunctionalTester $I): void
    {
        $I->submitForm('#contact-form', [
            'ContactForm[name]' => 'Cecilia',
            'ContactForm[email]' => 'pitch@example.com',
            'ContactForm[subject]' => 'Strike when the iron is hot',
            'ContactForm[body]' => 'We call your leads in seconds. 14-day free trial at https://leads.example. Not a fit? Opt out here: https://leads.example/unsubscribe',
            'ContactForm[verifyCode]' => 'testme',
        ]);
        $I->see('Thank you for contacting us.');
        $I->seeRecord(\common\models\Lead::class, ['email' => 'pitch@example.com', 'status' => \common\models\Lead::STATUS_SPAM]);
        $I->dontSeeEmailIsSent();
    }

    public function randomTextIsSavedAsSpam(FunctionalTester $I): void
    {
        $I->submitForm('#contact-form', [
            'ContactForm[name]' => 'NARYTHY542078NERTHRRTH',
            'ContactForm[email]' => 'random@example.com',
            'ContactForm[subject]' => 'TOTYJTRT542078TIGHTRTG',
            'ContactForm[body]' => 'MERYTRH542078MARTHHDF',
            'ContactForm[verifyCode]' => 'testme',
        ]);
        $I->seeRecord(\common\models\Lead::class, ['email' => 'random@example.com', 'status' => \common\models\Lead::STATUS_SPAM]);
    }

    public function realBriefMentioningSeoIsNotSpam(FunctionalTester $I): void
    {
        $I->submitForm('#contact-form', [
            'ContactForm[name]' => 'Priya Shah',
            'ContactForm[email]' => 'real-brief@example.com',
            'ContactForm[subject]' => 'New online store',
            'ContactForm[body]' => 'We need a shop with SEO services, lead generation forms, a free trial signup and an unsubscribe link in emails.',
            'ContactForm[verifyCode]' => 'testme',
        ]);
        $I->seeRecord(\common\models\Lead::class, ['email' => 'real-brief@example.com', 'status' => 'Pending']);
        $I->seeEmailIsSent();
    }
}
