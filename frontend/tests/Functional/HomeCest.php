<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\Testimonial;
use frontend\tests\Support\FunctionalTester;
use Yii;

final class HomeCest
{
    public function checkOpen(FunctionalTester $I): void
    {
        $I->amOnRoute('site/index');
        $I->see('cut to fit your business', 'h1');
        $I->amOnRoute('site/hosting');
        $I->see('Hosting plans', 'h2');
        $I->amOnRoute('site/about');
        $I->see('What we do', 'h2');
    }

    public function onlyPublishedTestimonialsShow(FunctionalTester $I): void
    {
        $I->amOnRoute('site/index');
        $I->dontSee('What our clients say');
        $row = ['role' => null, 'company' => null, 'country' => 'UK', 'sort_order' => 0, 'created_at' => time(), 'updated_at' => time()];
        $I->haveRecord(Testimonial::class, $row + ['client_name' => 'Hidden Client', 'quote' => 'Not yet approved.', 'is_published' => 0]);
        $I->amOnRoute('site/index');
        $I->dontSee('What our clients say');
        $I->haveRecord(Testimonial::class, $row + ['client_name' => 'Shown Client', 'quote' => 'Clear and <b>on time</b>.', 'is_published' => 1]);
        $I->amOnRoute('site/index');
        $I->see('What our clients say', 'h2');
        $I->see('Shown Client');
        $I->dontSee('Hidden Client');
        $I->seeInSource('Clear and &lt;b&gt;on time&lt;/b&gt;.');
    }

    public function pagesSendSecurityHeaders(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        \PHPUnit\Framework\Assert::assertSame('DENY', \Yii::$app->response->headers->get('X-Frame-Options'));
        \PHPUnit\Framework\Assert::assertSame('nosniff', \Yii::$app->response->headers->get('X-Content-Type-Options'));
    }
}
