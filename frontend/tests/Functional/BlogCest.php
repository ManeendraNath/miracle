<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\BlogCategory;
use common\models\BlogPost;
use frontend\tests\Support\FunctionalTester;

/** The public blog shows only published posts whose time has come, and lists them in the sitemap. */
final class BlogCest
{
    public function onlyPublishedPostsAreShown(FunctionalTester $I): void
    {
        $category = $I->haveRecord(BlogCategory::class, ['name' => 'Case Studies', 'slug' => 'case-studies', 'created_at' => time(), 'updated_at' => time()]);
        $post = ['summary' => 'A summary.', 'body' => '<p>Body text.</p>', 'category_id' => $category, 'created_at' => time(), 'updated_at' => time()];
        $I->haveRecord(BlogPost::class, $post + ['title' => 'Live post', 'slug' => 'live-post', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60]);
        $I->haveRecord(BlogPost::class, $post + ['title' => 'Draft post', 'slug' => 'draft-post', 'status' => BlogPost::STATUS_DRAFT, 'published_at' => null]);
        $I->haveRecord(BlogPost::class, $post + ['title' => 'Future post', 'slug' => 'future-post', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() + 86400]);

        $I->amOnPage('/blog');
        $I->seeResponseCodeIs(200);
        $I->see('Live post', 'h3');
        $I->dontSee('Draft post');
        $I->dontSee('Future post');
        $I->see('Case Studies', 'nav a');

        $I->amOnPage('/blog/category/case-studies');
        $I->see('Case Studies', 'h1');
        $I->see('Live post', 'h3');

        $I->amOnPage('/blog/live-post');
        $I->seeResponseCodeIs(200);
        $I->see('Live post', 'h1');
        $I->see('Discuss your project', 'a');
        $I->seeElement('link', ['rel' => 'canonical']);

        foreach (['/blog/draft-post', '/blog/future-post', '/blog/category/no-such-category'] as $hidden) {
            $I->amOnPage($hidden);
            $I->seeResponseCodeIs(404);
        }

        $I->amOnPage('/sitemap.xml');
        $I->seeResponseCodeIs(200);
        $I->seeInSource('/blog/live-post</loc>');
        $I->seeInSource('/blog/category/case-studies</loc>');
        $I->dontSeeInSource('/index</loc>');
        $I->dontSeeInSource('draft-post');
        $I->dontSeeInSource('future-post');

        $I->amOnPage('/blog/feed');
        $I->seeInSource('<title>Live post</title>');
        $I->dontSeeInSource('Draft post');
    }

    public function theSecondPageShowsOlderPosts(FunctionalTester $I): void
    {
        for ($n = 1; $n <= 10; $n++) {
            $I->haveRecord(BlogPost::class, ['title' => "Post $n", 'slug' => "post-$n", 'summary' => 'A summary.', 'body' => '<p>Body.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 1000 + $n, 'created_at' => time(), 'updated_at' => time()]);
        }
        $I->amOnPage('/blog?page=2');
        $I->see('Post 1', 'h2, h3');
        $I->dontSee('Post 10', 'h2, h3');
        $I->seeElement('link[rel=canonical][href$="/blog?page=2"]');
    }
}
