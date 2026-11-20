<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;
use Yii;

/** A copy sold to another business (PORTAL_ONLY): sign-in and the customer portal only. */
final class PortalOnlyCest
{
    public function _before(FunctionalTester $I): void
    {
        Yii::$app->params['portalOnly'] = true;
    }

    public function _after(FunctionalTester $I): void
    {
        Yii::$app->params['portalOnly'] = false;
    }

    public function theWebsitePagesAreGoneAndHomeIsTheSignIn(FunctionalTester $I): void
    {
        $I->amOnPage('/');
        $I->seeInCurrentUrl('login');
        $I->see('Log in to your account');
        $I->dontSee('Get a quote');
        $I->dontSeeElement('a[href="/store"]');
        $I->dontSeeElement('a[href="/hosting"]');
        $I->assertSame('noindex, nofollow', Yii::$app->response->headers->get('X-Robots-Tag'));
        foreach (['/site/about', '/site/contact', '/site/terms', '/blog', '/store', '/services', '/care-plans', '/sitemap.xml', '/feedback'] as $page) {
            $I->amOnPage($page);
            $I->seeResponseCodeIs(404);
        }
    }
}
