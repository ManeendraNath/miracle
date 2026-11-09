<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\Coupon;
use Yii;

/** What staff are told on the screens where a billing setting can mislead. */
final class BillingScreensCest
{
    public function _before(FunctionalTester $I): void
    {
        $id = $I->haveRecord(Admin::class, [
            'username' => 'billing-screens',
            'email' => 'billing-screens@example.test',
            'auth_key' => Yii::$app->security->generateRandomString(),
            'password_hash' => Yii::$app->security->generatePasswordHash('AdminTestPassword1', 4),
            'role' => Admin::getRoleSuperAdmin(),
            'status' => Admin::getStatusActive(),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $I->amLoggedInAs((int) $id);
    }

    public function serviceFormsExplainThePriceAndCycle(FunctionalTester $I): void
    {
        foreach (['hosting', 'domains', 'maintenance'] as $service) {
            $I->amOnRoute($service . '/create');
            $I->see('The price of one renewal cycle');
            $I->seeElement('input[placeholder^="Global setting ("]');
        }
    }

    public function couponDatesShowAsEntered(FunctionalTester $I): void
    {
        $id = $I->haveRecord(Coupon::class, [
            'code' => 'IST-CHECK',
            'name' => 'Time zone check',
            'type' => Coupon::TYPE_F,
            'amount' => 100,
            'is_active' => Coupon::IS_ACTIVE_Y,
            'end_date' => '2030-01-15 10:00:00',
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $I->amOnRoute('coupon/view', ['id' => $id]);
        $I->see('15 Jan 2030, 10:00 AM');
    }
}
