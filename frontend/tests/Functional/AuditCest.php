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
}
