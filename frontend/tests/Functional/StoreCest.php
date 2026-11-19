<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\Product;
use frontend\tests\Support\FunctionalTester;

/** The public store: only published products, filtered by technology, in the sitemap, with product data. */
final class StoreCest
{
    private function product(FunctionalTester $I, string $slug, array $extra = []): void
    {
        $I->haveRecord(Product::class, $extra + ['name' => ucfirst(str_replace('-', ' ', $slug)), 'slug' => $slug, 'technology' => 'html', 'kind' => 'theme', 'summary' => 'A summary.', 'description' => "First paragraph.\n\nSecond <b>paragraph</b>.", 'features' => "Fast\nResponsive", 'price_single_inr' => 2499, 'price_single_usd' => 39, 'status' => Product::STATUS_PUBLISHED, 'sort_order' => 0, 'created_at' => time(), 'updated_at' => time()]);
    }

    public function publishedProductsAreShownAndDraftsAreNot(FunctionalTester $I): void
    {
        $this->product($I, 'sky-html-theme', ['price_unlimited_inr' => 6999, 'price_unlimited_usd' => 99]);
        $this->product($I, 'press-plugin', ['technology' => 'wordpress', 'kind' => 'plugin']);
        $this->product($I, 'hidden-theme', ['status' => Product::STATUS_DRAFT]);

        $I->amOnPage('/store');
        $I->see('Sky html theme');
        $I->see('Press plugin');
        $I->dontSee('Hidden theme');
        $I->amOnPage('/store?technology=wordpress');
        $I->see('Press plugin');
        $I->dontSee('Sky html theme');
        $I->amOnPage('/store?technology=nope');
        $I->seeResponseCodeIs(404);

        $I->amOnPage('/store/sky-html-theme');
        $I->see('Single site');
        $I->see('Unlimited sites');
        $I->see('₹6,999');
        $I->seeInSource('Second &lt;b&gt;paragraph&lt;/b&gt;.');
        $I->seeInSource('"@type":"Product"');
        $I->amOnPage('/store/press-plugin');
        $I->dontSee('Unlimited sites');
        $I->amOnPage('/store/hidden-theme');
        $I->seeResponseCodeIs(404);

        $I->amOnPage('/sitemap.xml');
        $I->seeInSource('/store/sky-html-theme</loc>');
        $I->seeInSource('/store</loc>');
        $I->dontSeeInSource('hidden-theme');
    }
}
