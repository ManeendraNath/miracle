<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use Yii;

/** Site Settings > Payments: gateways per client location, saved by a Superadmin. */
final class SiteSettingPaymentsCest
{
    private const PASSWORD = 'AdminTestPassword1';

    public function _before(FunctionalTester $I): void
    {
        // A recent check: the page must not call the exchange rate API from the tests.
        if ((new \yii\db\Query())->from('{{%site_setting}}')->where(['id' => 1])->exists()) {
            Yii::$app->db->createCommand()->update('{{%site_setting}}', ['usd_rate_checked_at' => time()], ['id' => 1])->execute();
        } else {
            Yii::$app->db->createCommand()->insert('{{%site_setting}}', ['id' => 1, 'site_name' => 'Test', 'site_url' => 'https://example.test', 'mobile_1' => 9999999999, 'email_1' => 'test@example.test', 'usd_rate_checked_at' => time(), 'updated_at' => time()])->execute();
        }
        $I->haveRecord(Admin::class, ['username' => 'settings-root', 'email' => 'settings-root@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => Admin::getRoleSuperAdmin(), 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'settings-root', 'LoginForm[password]' => self::PASSWORD]);
    }

    public function gatewaysAreSwitchedOnPerClientLocation(FunctionalTester $I): void
    {
        $I->amOnRoute('site-setting/index');
        $I->seeResponseCodeIs(200);
        $I->see('Razorpay for clients in India');
        $I->see('PayPal for clients abroad');
        $I->submitForm('#site-settings-form', ['SiteSetting[razorpay_india]' => '1', 'SiteSetting[razorpay_abroad]' => '0', 'SiteSetting[paypal_abroad]' => '1', 'SiteSetting[usd_rate_auto]' => '0', 'SiteSetting[usd_rate_margin]' => '3', 'SiteSetting[usd_inr_rate]' => '85']);
        $I->seeRecord(\common\models\SiteSetting::class, ['id' => 1, 'razorpay_india' => 1, 'razorpay_abroad' => 0, 'paypal_abroad' => 1, 'usd_rate_auto' => 0]);
    }
}
