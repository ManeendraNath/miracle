<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\BlogCategory;
use common\models\BlogComment;
use common\models\BlogPost;
use Yii;

/** Writing a blog post in the admin. */
final class BlogPostCest
{
    private const PASSWORD = 'BlogWriter123';

    public function _before(FunctionalTester $I): void
    {
        $I->haveRecord(Admin::class, ['username' => 'blog-writer', 'email' => 'blog-writer@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => 'Manager', 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'blog-writer', 'LoginForm[password]' => self::PASSWORD]);
    }

    public function aPostIsSavedWithItsAddressMadeFromTheTitle(FunctionalTester $I): void
    {
        $I->amOnRoute('blog-post/create');
        $I->seeResponseCodeIs(200);
        $I->seeCheckboxIsChecked('#blogpost-send_newsletter');
        $I->seeCheckboxIsChecked('#blogpost-comments_open');
        $I->submitForm('#blog-post-form', [
            'BlogPost[title]' => 'Hiring an offshore developer',
            'BlogPost[summary]' => 'What to check first.',
            'BlogPost[body]' => '<h2>Start here</h2><p>Text.</p><script>alert(1)</script>',
            'BlogPost[status]' => (string) BlogPost::STATUS_PUBLISHED,
            'BlogPost[slug]' => '',
            'BlogPost[publishAt]' => '',
        ]);
        $I->see('Post published and added to the sitemap automatically');
        $I->see('Request indexing.');
        $post = BlogPost::findOne(['slug' => 'hiring-an-offshore-developer']);
        $I->assertNotNull($post);
        $I->assertTrue($post->isPublic());
        $I->assertStringNotContainsString('<script', $post->body);
        $I->amOnRoute('blog-post/preview', ['id' => $post->id]);
        $I->see('Hiring an offshore developer', 'h1');

        $I->amOnRoute('blog-post/update', ['id' => $post->id]);
        $I->submitForm('#blog-post-form', ['BlogPost[summary]' => 'What to check first, in order.']);
        $I->see('The sitemap shows the update by itself; nothing to do in Search Console.');
        $I->submitForm('#blog-post-form', ['BlogPost[slug]' => 'offshore-developer']);
        $I->see('Its web address changed');
        $I->see('/blog/offshore-developer');
    }

    public function anUploadWithoutAFileIsRefused(FunctionalTester $I): void
    {
        $I->amOnRoute('blog-post/index');
        $I->see('Views', 'th');
        $I->sendAjaxPostRequest('/blog-post/upload-image', [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->seeResponseCodeIs(422);
        $I->seeInSource('Choose an image to upload.');
    }

    public function aCategoryGetsItsWebAddressFromTheName(FunctionalTester $I): void
    {
        $I->amOnRoute('blog-category/create');
        $I->submitForm('form[action*="blog-category/create"]', ['BlogCategory[name]' => 'Case Studies', 'BlogCategory[slug]' => '']);
        $I->seeRecord(BlogCategory::class, ['name' => 'Case Studies', 'slug' => 'case-studies']);
    }

    public function staffApproveAndAnswerAComment(FunctionalTester $I): void
    {
        $postId = (int) $I->haveRecord(BlogPost::class, ['title' => 'Asked post', 'slug' => 'asked-post', 'summary' => 'A summary.', 'body' => '<p>Body.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60, 'created_at' => time(), 'updated_at' => time()]);
        $id = (int) $I->haveRecord(BlogComment::class, ['post_id' => $postId, 'author_name' => 'Ana', 'author_email' => 'ana@example.test', 'body' => 'Do you build in Laravel?', 'status' => BlogComment::STATUS_PENDING, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('blog-comment/index');
        $I->see('Do you build in Laravel?');
        $I->see('Comments', '.nav-sidebar');

        $I->amOnRoute('blog-comment/reply', ['id' => $id]);
        $I->submitForm('#reply-form', ['BlogComment[body]' => 'Yes, and Yii.', 'BlogComment[status]' => (string) BlogComment::STATUS_SPAM, 'BlogComment[post_id]' => '999']);
        $I->see('Reply posted.');
        $I->seeRecord(BlogComment::class, ['id' => $id, 'status' => BlogComment::STATUS_APPROVED]);
        $I->seeRecord(BlogComment::class, ['parent_id' => $id, 'post_id' => $postId, 'body' => 'Yes, and Yii.', 'status' => BlogComment::STATUS_APPROVED, 'author_email' => null]);

        $I->sendAjaxPostRequest('/blog-comment/spam?id=' . $id, [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->seeRecord(BlogComment::class, ['id' => $id, 'status' => BlogComment::STATUS_SPAM]);
    }

    public function staffSeeAndDeleteSubscribers(FunctionalTester $I): void
    {
        $id = (int) $I->haveRecord(\common\models\BlogSubscriber::class, ['email' => 'list@example.test', 'token' => str_repeat('t', 40), 'status' => \common\models\BlogSubscriber::STATUS_SUBSCRIBED, 'confirmed_at' => time(), 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnRoute('blog-subscriber/index');
        $I->see('list@example.test');
        $I->see('Subscribers', '.nav-sidebar');
        $I->amOnRoute('blog-subscriber/index', ['q' => 'nobody']);
        $I->dontSee('list@example.test');
        $I->sendAjaxPostRequest('/blog-subscriber/delete?id=' . $id, [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->dontSeeRecord(\common\models\BlogSubscriber::class, ['id' => $id]);
    }

    public function postsGoToTheTrashAndComeBackAndBulkActionsWork(FunctionalTester $I): void
    {
        $row = ['summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_DRAFT, 'created_at' => time(), 'updated_at' => time()];
        $a = (int) $I->haveRecord(BlogPost::class, $row + ['title' => 'Post A', 'slug' => 'post-a']);
        $b = (int) $I->haveRecord(BlogPost::class, $row + ['title' => 'Post B', 'slug' => 'post-b']);
        $I->amOnRoute('blog-post/index');
        $token = $I->grabAttributeFrom('meta[name=csrf-token]', 'content');

        $I->sendAjaxPostRequest('/blog-post/bulk', ['_csrf-backend' => $token, 'bulk_action' => 'publish', 'selection' => [$a, $b]]);
        $I->assertTrue(BlogPost::findOne($a)->isPublic());
        $I->assertTrue(BlogPost::findOne($b)->isPublic());

        $I->sendAjaxPostRequest('/blog-post/delete?id=' . $a, ['_csrf-backend' => $token]);
        $I->assertSame('In trash', BlogPost::findOne($a)->stateLabel());
        $I->amOnRoute('blog-post/index', ['trash' => 1]);
        $I->see('Post A');
        $I->dontSee('Post B', 'td');
        $I->sendAjaxPostRequest('/blog-post/untrash?id=' . $a, ['_csrf-backend' => $token]);
        $I->assertTrue(BlogPost::findOne($a)->isPublic());

        $I->sendAjaxPostRequest('/blog-post/bulk', ['_csrf-backend' => $token, 'bulk_action' => 'trash', 'selection' => [$b]]);
        $I->sendAjaxPostRequest('/blog-post/bulk', ['_csrf-backend' => $token, 'bulk_action' => 'purge', 'selection' => [$a, $b]]);
        $I->seeRecord(BlogPost::class, ['id' => $a]);
        $I->dontSeeRecord(BlogPost::class, ['id' => $b]);

        $I->sendAjaxPostRequest('/blog-post/duplicate?id=' . $a, ['_csrf-backend' => $token]);
        $I->seeRecord(BlogPost::class, ['slug' => 'post-a-copy', 'status' => BlogPost::STATUS_DRAFT]);
        $I->amOnRoute('blog-post/stats', ['id' => $a]);
        $I->see('Views a day, last 30 days');
    }

    public function theMediaLibraryListsImagesAndDeletesOnlyUnusedOnes(FunctionalTester $I): void
    {
        $dir = Yii::getAlias('@frontend/web/' . BlogPost::UPLOAD_DIR . '/2000-01');
        @mkdir($dir, 0775, true);
        $unused = BlogPost::UPLOAD_DIR . '/2000-01/aaaaaaaaaaaaaaaa.png';
        $used = BlogPost::UPLOAD_DIR . '/2000-01/bbbbbbbbbbbbbbbb.png';
        $photo = BlogPost::UPLOAD_DIR . '/2000-01/cccccccccccccccc.png';
        Admin::updateAll(['photo' => $photo], ['username' => 'blog-writer']);
        foreach ([$unused, $used, $photo] as $path) {
            file_put_contents(Yii::getAlias('@frontend/web/' . $path), base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        }
        $I->haveRecord(BlogPost::class, ['title' => 'Pictured', 'slug' => 'pictured', 'summary' => 'S.', 'body' => '<p><img src="https://example.test/' . $used . '"></p>', 'status' => BlogPost::STATUS_DRAFT, 'created_at' => time(), 'updated_at' => time()]);
        try {
            $I->amOnRoute('blog-media/index');
            $I->see('Used in: Pictured');
            $I->see('Not used in any post');
            $I->see('Author photo of blog-writer');
            $token = $I->grabAttributeFrom('meta[name=csrf-token]', 'content');
            $I->sendAjaxPostRequest('/blog-media/delete', ['_csrf-backend' => $token, 'path' => $used]);
            $I->assertFileExists(Yii::getAlias('@frontend/web/' . $used));
            $I->sendAjaxPostRequest('/blog-media/delete', ['_csrf-backend' => $token, 'path' => $photo]);
            $I->assertFileExists(Yii::getAlias('@frontend/web/' . $photo));
            $I->sendAjaxPostRequest('/blog-media/delete', ['_csrf-backend' => $token, 'path' => $unused]);
            $I->assertFileDoesNotExist(Yii::getAlias('@frontend/web/' . $unused));
            $I->sendAjaxPostRequest('/blog-media/delete', ['_csrf-backend' => $token, 'path' => '../../index.php']);
            $I->assertFileExists(Yii::getAlias('@frontend/web/index.php'));
        } finally {
            @unlink(Yii::getAlias('@frontend/web/' . $used));
            @unlink(Yii::getAlias('@frontend/web/' . $photo));
            @unlink(Yii::getAlias('@frontend/web/' . $unused));
            @rmdir($dir);
        }
    }

    public function anAdminSetsASearchTitleForAFixedPage(FunctionalTester $I): void
    {
        // Public website text: Admins only, like testimonials. The Manager signed in by _before is refused.
        $I->amOnRoute('seo-page/index');
        $I->dontSee('Upgrade your existing website');
        $I->haveRecord(Admin::class, ['username' => 'seo-admin', 'email' => 'seo-admin@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => 'Admin', 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->sendAjaxPostRequest('/auth/logout', [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'seo-admin', 'LoginForm[password]' => self::PASSWORD]);
        $I->amOnRoute('seo-page/index');
        $I->see('Upgrade your existing website');
        $I->amOnRoute('seo-page/update', ['route' => 'site/audit']);
        $I->submitForm('form', ['SeoPage[title]' => '  Website Upgrade Service  ', 'SeoPage[description]' => 'Faster, safer sites.', 'SeoPage[route]' => 'site/login']);
        $I->seeRecord(\common\models\SeoPage::class, ['route' => 'site/audit', 'title' => 'Website Upgrade Service']);
        $I->dontSeeRecord(\common\models\SeoPage::class, ['route' => 'site/login']);
        $I->amOnRoute('seo-page/update', ['route' => 'site/login']);
        $I->seeResponseCodeIs(404);
    }

    public function anAdminAddsAStoreProductAndAManagerCannot(FunctionalTester $I): void
    {
        $I->amOnRoute('product/index');
        $I->seeResponseCodeIs(403);
        $I->haveRecord(Admin::class, ['username' => 'store-admin', 'email' => 'store-admin@example.test', 'auth_key' => Yii::$app->security->generateRandomString(), 'password_hash' => Yii::$app->security->generatePasswordHash(self::PASSWORD), 'role' => 'Admin', 'status' => Admin::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $I->sendAjaxPostRequest('/auth/logout', [Yii::$app->request->csrfParam => $I->grabAttributeFrom('meta[name=csrf-token]', 'content')]);
        $I->amOnRoute('auth/login');
        $I->submitForm('#login-form', ['LoginForm[username]' => 'store-admin', 'LoginForm[password]' => self::PASSWORD]);

        $fields = ['Product[name]' => 'Nova Shop Theme', 'Product[technology]' => 'wordpress', 'Product[kind]' => 'theme', 'Product[summary]' => 'A store theme.', 'Product[description]' => 'Text.', 'Product[price_single_inr]' => '2999', 'Product[price_single_usd]' => '45', 'Product[status]' => '1'];
        $I->amOnRoute('product/create');
        $I->submitForm('form', $fields + ['Product[price_unlimited_inr]' => '7999', 'Product[price_unlimited_usd]' => '']);
        $I->see('Give both unlimited-sites prices, or leave both empty.');
        $I->dontSeeRecord(\common\models\Product::class, ['slug' => 'nova-shop-theme']);

        $I->amOnRoute('product/create');
        $I->submitForm('form', $fields);
        $I->seeRecord(\common\models\Product::class, ['slug' => 'nova-shop-theme', 'status' => 1, 'price_unlimited_inr' => null]);
        $I->see('It is in the store');
    }
}
