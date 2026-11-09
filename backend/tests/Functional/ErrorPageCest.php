<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\AdminNotification;

/** The admin error page is drawn in the staff layout, and anyone can reach it. */
final class ErrorPageCest
{
    public function signedOutVisitorSeesNoStaffAlerts(FunctionalTester $I): void
    {
        $I->haveRecord(AdminNotification::class, [
            'type' => 'lead',
            'title' => 'New Lead',
            'message' => 'Name: Secret Visitor',
            'is_read' => 0,
            'created_at' => time(),
        ]);
        $I->amOnPage('/xyz-not-a-page');
        $I->seeResponseCodeIs(404);
        $I->dontSee('Secret Visitor');
        \PHPUnit\Framework\Assert::assertSame(0, (int) AdminNotification::visibleTo(null)->count());
    }
}
