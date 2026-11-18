<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\AuditRequest;
use frontend\tests\Support\FunctionalTester;

/** Public audit request form: captcha and a real email are required. */
final class AuditCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->amOnRoute('site/audit');
    }

    public function rejectsBadEmailAndWrongCaptcha(FunctionalTester $I): void
    {
        $I->submitForm('#infrastructure-audit-form', [
            'AuditRequest[name]' => 'Spam Bot',
            'AuditRequest[email]' => 'not-an-email',
            'AuditRequest[message]' => 'Buy now',
            'AuditRequest[verifyCode]' => 'wrong',
        ]);
        $I->seeValidationError('The verification code is incorrect');
        $I->see('is not a valid email address');
        $I->dontSeeRecord(AuditRequest::class, ['name' => 'Spam Bot']);
    }

    public function savesAValidRequest(FunctionalTester $I): void
    {
        $I->submitForm('#infrastructure-audit-form', [
            'AuditRequest[name]' => 'Audit Tester',
            'AuditRequest[email]' => 'audit-tester@example.com',
            'AuditRequest[company_url]' => 'example.com',
            'AuditRequest[message]' => 'Please review our site.',
            'AuditRequest[verifyCode]' => 'testme',
        ]);
        $I->seeRecord(AuditRequest::class, ['email' => 'audit-tester@example.com', 'company_url' => 'https://example.com']);
    }

    public function keepsARequestCaughtByTheBotTrapAsASpamLead(FunctionalTester $I): void
    {
        $I->submitForm('#infrastructure-audit-form', [
            'AuditRequest[name]' => 'Autofilled Person',
            'AuditRequest[email]' => 'autofilled@example.com',
            'AuditRequest[company_url]' => 'example.org',
            'AuditRequest[message]' => 'Our site is slow.',
            'AuditRequest[verifyCode]' => 'testme',
            'mw_hp_extra' => 'filled by an extension',
        ]);
        $I->see('We have your website details');
        $I->dontSeeRecord(AuditRequest::class, ['email' => 'autofilled@example.com']);
        $I->seeRecord(\common\models\Lead::class, ['email' => 'autofilled@example.com', 'status' => \common\models\Lead::STATUS_SPAM]);
    }
}
