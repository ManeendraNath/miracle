<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\SeoPage;
use common\models\SiteSetting;
use frontend\tests\Support\FunctionalTester;

/** What search engines read: staff-set titles, verification codes, structured data, pages kept out of results. */
final class SeoCest
{
    private function resetSettings(): void
    {
        (new \ReflectionProperty(\common\components\CustomYii::class, '_settings'))->setValue(null, null);
    }

    public function staffTitlesAndVerificationCodesReachThePage(FunctionalTester $I): void
    {
        $I->haveRecord(SeoPage::class, ['route' => 'site/about', 'title' => 'Web Developers in Delhi', 'description' => 'Who we are.', 'updated_at' => time()]);
        SiteSetting::updateAll(['google_site_verification' => 'g-code_1', 'bing_site_verification' => 'B1NG']);
        $this->resetSettings();
        try {
            $I->amOnPage('/about');
            $I->seeInTitle('Web Developers in Delhi | Miracle Web Technologies');
            $I->seeElement('meta[name=description][content="Who we are."]');
            $I->seeElement('meta[name=google-site-verification][content=g-code_1]');
            $I->seeElement('meta[name="msvalidate.01"][content=B1NG]');
        } finally {
            SiteSetting::updateAll(['google_site_verification' => null, 'bing_site_verification' => null]);
            $this->resetSettings();
        }
    }

    public function theHomePageNamesTheCompanyAndSignInPagesStayOutOfResults(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeInSource('"@type":"Organization"');
        $I->seeInSource('"@type":"WebSite"');
        $I->dontSeeElement('meta[name=robots]');
        foreach (['/site/login', '/site/signup', '/site/request-password-reset'] as $page) {
            $I->amOnPage($page);
            $I->seeElement('meta[name=robots][content="noindex, follow"]');
        }
    }

    public function theIndexNowKeyIsPublished(FunctionalTester $I): void
    {
        $I->amOnPage('/indexnow-key.txt');
        $I->seeResponseCodeIs(200);
        $key = (string) SiteSetting::find()->select('indexnow_key')->where(['id' => 1])->scalar();
        $I->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $key);
        $I->seeInSource($key);
    }

    public function everyPublicPageIsInTheSitemapByItselfAndAccountPagesAreNot(FunctionalTester $I): void
    {
        $pages = array_keys(SeoPage::pages());
        $I->assertSame('site/index', $pages[0]);
        foreach (['site/about', 'site/hosting', 'site/audit', 'site/contact', 'site/privacy', 'site/terms', 'site/refund-policy', 'site/shipping-policy', 'blog/index'] as $route) {
            $I->assertContains($route, $pages);
        }
        $I->assertNotContains('site/login', $pages);
        $I->assertNotContains('site/csp-report', $pages);
        $I->amOnPage('/sitemap.xml');
        $I->seeInSource('/refund-policy</loc>');
        $I->dontSeeInSource('/login</loc>');
        $I->dontSeeInSource('/signup</loc>');
    }
}
