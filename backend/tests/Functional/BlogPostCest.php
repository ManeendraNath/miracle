<?php

declare(strict_types=1);

namespace backend\tests\Functional;

use backend\tests\Support\FunctionalTester;
use common\models\Admin;
use common\models\BlogCategory;
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
        $I->submitForm('#blog-post-form', [
            'BlogPost[title]' => 'Hiring an offshore developer',
            'BlogPost[summary]' => 'What to check first.',
            'BlogPost[body]' => '<h2>Start here</h2><p>Text.</p><script>alert(1)</script>',
            'BlogPost[status]' => (string) BlogPost::STATUS_PUBLISHED,
            'BlogPost[slug]' => '',
            'BlogPost[publishAt]' => '',
        ]);
        $I->see('Post published.');
        $post = BlogPost::findOne(['slug' => 'hiring-an-offshore-developer']);
        $I->assertNotNull($post);
        $I->assertTrue($post->isPublic());
        $I->assertStringNotContainsString('<script', $post->body);
        $I->amOnRoute('blog-post/preview', ['id' => $post->id]);
        $I->see('Hiring an offshore developer', 'h1');
    }

    public function anUploadWithoutAFileIsRefused(FunctionalTester $I): void
    {
        $I->amOnRoute('blog-post/index');
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
}
