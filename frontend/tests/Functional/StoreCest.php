<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\fixtures\UserFixture;
use common\models\CustomerBillingProfile;
use common\models\Product;
use common\models\ProductOrder;
use common\models\User;
use frontend\tests\Support\FunctionalTester;

/** The public store: only published products, filtered by technology, in the sitemap, with product data. */
final class StoreCest
{
    public function _fixtures(): array
    {
        return ['user' => ['class' => UserFixture::class, 'dataFile' => codecept_data_dir() . 'login_data.php']];
    }

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

    public function aBuyerSignsInGivesBillingDetailsAndGetsAnInvoice(FunctionalTester $I): void
    {
        $this->product($I, 'buy-me-theme');
        $I->amOnPage('/store/buy-me-theme');
        $I->click('a[href*="/store/buy/buy-me-theme"]');
        $I->seeInCurrentUrl('login');
        $I->see('to buy Buy me theme');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'erau', 'LoginForm[password]' => 'password_0']);
        // Billing details first: they go on the invoice.
        $I->seeInCurrentUrl('account/profile');
        $userId = (int) User::findOne(['username' => 'erau'])->id;
        $I->haveRecord(CustomerBillingProfile::class, ['user_id' => $userId, 'legal_name' => 'Erau', 'address_line_1' => '1 Road', 'city' => 'Delhi', 'state' => 'Delhi', 'state_code' => '07', 'country' => 'India', 'postal_code' => '110001', 'created_at' => time(), 'updated_at' => time()]);

        $I->amOnPage('/store/buy/buy-me-theme?licence=single');
        $I->see('Your order');
        $I->see('₹2,499');
        $I->see('UPI or bank transfer');
        $I->click('Continue to payment');
        $I->seeInCurrentUrl('invoice/view');
        $order = ProductOrder::findOne(['user_id' => $userId]);
        $I->assertNotNull($order);
        $I->assertFalse($order->isPaid());

        $I->amOnRoute('purchase/index');
        $I->see('Buy me theme');
        $I->see('Awaiting payment');
        $I->amOnRoute('purchase/download', ['id' => $order->id]);
        $I->seeResponseCodeIs(404);
        // No unlimited licence for this product.
        $I->amOnPage('/store/buy/buy-me-theme?licence=unlimited');
        $I->seeResponseCodeIs(404);
    }

    public function aPaidBuyerDownloadsAndTheFirstDownloadIsRecorded(FunctionalTester $I): void
    {
        $dir = \Yii::getAlias('@storage/' . Product::FILES_DIR);
        \yii\helpers\FileHelper::createDirectory($dir);
        $file = 'test-' . bin2hex(random_bytes(6)) . '.zip';
        file_put_contents($dir . '/' . $file, 'PK zip bytes');
        try {
            $this->product($I, 'downloadable-theme', ['download_file' => $file, 'download_name' => 'downloadable-theme-1.0.zip', 'version' => '1.0']);
            $I->amOnRoute('site/login');
            $I->submitForm('#login-form', ['LoginForm[username]' => 'erau', 'LoginForm[password]' => 'password_0']);
            $userId = (int) User::findOne(['username' => 'erau'])->id;
            $I->haveRecord(CustomerBillingProfile::class, ['user_id' => $userId, 'legal_name' => 'Erau', 'address_line_1' => '1 Road', 'city' => 'Delhi', 'state' => 'Delhi', 'state_code' => '07', 'country' => 'India', 'postal_code' => '110001', 'created_at' => time(), 'updated_at' => time()]);
            $invoiceId = $I->haveRecord(\common\models\Invoice::class, ['user_id' => $userId, 'invoice_number' => 'ST-' . bin2hex(random_bytes(3)), 'client_name' => 'Erau', 'subtotal_amount' => 2499, 'discount_amount' => 0, 'total_payable' => 2499, 'status' => \common\models\Invoice::STATUS_PAID, 'created_at' => time(), 'updated_at' => time()]);
            $orderId = $I->haveRecord(ProductOrder::class, ['user_id' => $userId, 'product_id' => (int) Product::findOne(['slug' => 'downloadable-theme'])->id, 'licence' => 'single', 'invoice_id' => $invoiceId, 'licence_key' => 'AAAAA-BBBBB-CCCCC-DDDDD', 'paid_at' => time(), 'created_at' => time(), 'updated_at' => time()]);

            $I->amOnRoute('purchase/index');
            $I->see('Latest version 1.0');
            $I->see('Setup help and support until');
            $I->seeInField('#key-' . $orderId, 'AAAAA-BBBBB-CCCCC-DDDDD');
            $I->amOnRoute('purchase/download', ['id' => $orderId]);
            $I->seeResponseCodeIs(200);
            $I->assertNotNull(ProductOrder::findOne($orderId)->first_downloaded_at);
        } finally {
            @unlink($dir . '/' . $file);
        }
    }

    public function theLicenceTermsAreLinkedAndInTheSitemap(FunctionalTester $I): void
    {
        $I->amOnPage('/licence');
        $I->see('Licence Terms for Themes & Plugins');
        $I->see('full refund if you ask before your first download');
        $I->amOnPage('/refund-policy');
        $I->see('Website themes and plugins from our store');
        $I->amOnPage('/sitemap.xml');
        $I->seeInSource('/licence</loc>');
    }
}
