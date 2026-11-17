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
}
