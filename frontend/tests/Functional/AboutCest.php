<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;

/** About and the policy pages payment-gateway KYC checks are public and linked from the footer. */
final class AboutCest
{
    public function checkAbout(FunctionalTester $I): void
    {
        $I->amOnRoute('site/about');
        $I->see('About Us', 'h1');
    }

    public function checkPolicyPages(FunctionalTester $I): void
    {
        foreach (['terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'refund-policy' => 'Refund & Cancellation Policy', 'shipping-policy' => 'Shipping & Delivery Policy'] as $route => $heading) {
            $I->amOnRoute('site/' . $route);
            $I->seeResponseCodeIs(200);
            $I->see($heading, 'h1');
            $I->see('Contact us', 'h2');
        }
        foreach (['Terms & Conditions', 'Privacy Policy', 'Refund & Cancellation', 'Shipping & Delivery', 'About us'] as $link) {
            $I->see($link, 'footer a');
        }
    }
}
