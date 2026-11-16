<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\models\searches\InvoiceSearch;
use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use Yii;

/** A link from an alert email, opened while signed out, leads to its page once signed in. */
final class ReturnAfterLoginCest
{
    public function signingInOpensTheLinkedPage(FunctionalTester $I): void
    {
        $I->haveRecord(Admin::class, ['username' => 'link-opener', 'email' => 'link-opener@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash('LinkOpener123'), 'role' => 'Manager', 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnPage('/blog-category/index');
        $I->seeInCurrentUrl('/auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'link-opener', 'LoginForm[password]' => 'LinkOpener123']);
        $I->seeInCurrentUrl('/blog-category/index');
        // Used once: a later sign-in starts on the dashboard.
        $I->assertNull(Yii::$app->session->get(Yii::$app->user->returnUrlParam));
    }


    public function outstandingMeansUnpaidOrPartlyPaid(FunctionalTester $I): void
    {
        $search = new InvoiceSearch();
        $query = $search->search(['InvoiceSearch' => ['status' => InvoiceSearch::OUTSTANDING]])->query;
        $I->assertInstanceOf(\yii\db\ActiveQuery::class, $query);
        $I->assertStringContainsString("'Unpaid', 'Partially_Paid'", $query->createCommand()->getRawSql());
    }
}
