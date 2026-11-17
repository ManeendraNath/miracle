<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use common\models\BlogCategory;
use common\models\BlogComment;
use common\models\BlogSubscriber;
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

    public function aReaderCountsOnceADayAndABotNotAtAll(FunctionalTester $I): void
    {
        $id = $I->haveRecord(BlogPost::class, ['title' => 'Counted post', 'slug' => 'counted-post', 'summary' => 'A summary.', 'body' => '<p>Body.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60, 'created_at' => time(), 'updated_at' => time()]);
        $I->haveHttpHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/140.0 Safari/537.36');
        $I->amOnPage('/blog/counted-post');
        $I->amOnPage('/blog/counted-post');
        $I->assertSame(['total' => 1, 'recent' => 1], \common\models\BlogPostView::counts([(int) $id])[(int) $id] ?? null);
        $I->haveHttpHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $I->amOnPage('/blog/counted-post');
        $I->assertSame(1, \common\models\BlogPostView::counts([(int) $id])[(int) $id]['total']);
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

    public function everyPageNamesItsOneAddress(FunctionalTester $I): void
    {
        $I->amOnPage('/site/about');
        $I->seeElement('link[rel=canonical][href$="/about"]');
        $I->amOnPage('/contact?subject=Project+enquiry');
        $I->seeElement('link[rel=canonical][href$="/contact"]');
    }

    public function aCommentShowsOnlyOnceStaffApproveIt(FunctionalTester $I): void
    {
        $id = (int) $I->haveRecord(BlogPost::class, ['title' => 'Talk post', 'slug' => 'talk-post', 'summary' => 'A summary.', 'body' => '<p>Body.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnPage('/blog/talk-post');
        $I->see('Leave a comment', 'h3');
        $I->submitForm('#comment-form', ['BlogComment[author_name]' => 'Ana', 'BlogComment[author_email]' => 'ana@example.test', 'BlogComment[body]' => "Useful, thanks.\n<b>Two</b> lines."]);
        $I->see('Your comment will appear here once we have read it.');
        $I->dontSee('Useful, thanks.');
        $comment = BlogComment::findOne(['post_id' => $id]);
        $I->assertSame(BlogComment::STATUS_PENDING, $comment->status);
        $I->seeRecord(\common\models\AdminNotification::class, ['title' => 'New Blog Comment']);

        $comment->updateAttributes(['status' => BlogComment::STATUS_APPROVED]);
        $I->haveRecord(BlogComment::class, ['post_id' => $id, 'parent_id' => $comment->id, 'author_name' => 'Team member', 'body' => 'Glad it helped.', 'status' => BlogComment::STATUS_APPROVED, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnPage('/blog/talk-post');
        $I->see('1 comment', 'h2');
        $I->see('Useful, thanks.');
        $I->seeInSource('&lt;b&gt;Two&lt;/b&gt;');
        $I->see('Glad it helped.');
        $I->dontSee('ana@example.test');

        BlogPost::updateAll(['comments_open' => 0], ['id' => $id]);
        $I->amOnPage('/blog/talk-post');
        $I->see('Comments are closed on this post.');
        $I->dontSeeElement('#comment-form');
        $I->see('Useful, thanks.');
    }

    public function spamAndFloodsAreKeptOff(FunctionalTester $I): void
    {
        $I->haveServerParameter('REMOTE_ADDR', '203.0.113.7');
        $id = (int) $I->haveRecord(BlogPost::class, ['title' => 'Busy post', 'slug' => 'busy-post', 'summary' => 'A summary.', 'body' => '<p>Body.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60, 'created_at' => time(), 'updated_at' => time()]);
        $I->amOnPage('/blog/busy-post');
        $I->submitForm('#comment-form', ['BlogComment[author_name]' => 'Bot', 'BlogComment[author_email]' => 'b@example.test', 'BlogComment[body]' => 'Hi', 'mw_hp_extra' => 'filled']);
        $I->see('Your comment will appear here', '#comments [role=status]');
        $I->seeRecord(BlogComment::class, ['post_id' => $id, 'author_name' => 'Bot', 'status' => BlogComment::STATUS_SPAM]);

        $I->submitForm('#comment-form', ['BlogComment[author_name]' => 'Seller', 'BlogComment[author_email]' => 's@example.test', 'BlogComment[body]' => 'http://a.test http://b.test http://c.test']);
        $I->seeRecord(BlogComment::class, ['post_id' => $id, 'author_name' => 'Seller', 'status' => BlogComment::STATUS_SPAM]);

        $I->submitForm('#comment-form', ['BlogComment[author_name]' => 'Reader 1', 'BlogComment[author_email]' => 'r@example.test', 'BlogComment[body]' => 'Comment 1']);
        $I->seeRecord(BlogComment::class, ['author_name' => 'Reader 1', 'status' => BlogComment::STATUS_PENDING]);
        $I->submitForm('#comment-form', ['BlogComment[author_name]' => 'Reader 3', 'BlogComment[author_email]' => 'r@example.test', 'BlogComment[body]' => 'Comment 3']);
        $I->see('Please wait', '#comments [role=alert]');
        $I->dontSeeRecord(BlogComment::class, ['author_name' => 'Reader 3']);
    }

    public function aReaderSubscribesConfirmsAndUnsubscribes(FunctionalTester $I): void
    {
        $I->amOnPage('/blog');
        $I->see('Get new posts by email', 'h2');
        $I->amOnPage('/blog/subscribe');
        $I->submitForm('#subscribe-page-title + p + form', ['BlogSubscriber[email]' => 'not-an-email']);
        $I->see('Enter a valid email address.');
        $I->submitForm('#subscribe-page-title + p + form', ['BlogSubscriber[email]' => 'reader@example.test']);
        $I->see('we sent you an email with a link to confirm', '[role=status]');
        $I->seeEmailIsSent(1);
        $subscriber = BlogSubscriber::findOne(['email' => 'reader@example.test']);
        $I->assertSame(BlogSubscriber::STATUS_PENDING, $subscriber->status);
        $I->assertStringContainsString('/blog/confirm?token=' . $subscriber->token, quoted_printable_decode((string) $I->grabLastSentEmail()->toString()));

        $I->amOnPage('/blog/confirm?token=' . $subscriber->token);
        $I->see('Send new blog posts to reader@example.test?');
        $I->assertSame(BlogSubscriber::STATUS_PENDING, $subscriber->refresh() ? $subscriber->status : null, 'opening the link alone (a mail scanner) does not subscribe');
        $I->click('Yes, subscribe me');
        $I->see('You are subscribed', 'h1');
        $I->assertSame(BlogSubscriber::STATUS_SUBSCRIBED, $subscriber->refresh() ? $subscriber->status : null);

        $I->amOnPage('/blog/unsubscribe?token=' . $subscriber->token);
        $I->see('Stop sending new blog posts to reader@example.test?');
        $I->assertSame(BlogSubscriber::STATUS_SUBSCRIBED, $subscriber->refresh() ? $subscriber->status : null);
        // A mail app's one-click unsubscribe: a plain POST, no form token.
        $I->sendAjaxPostRequest('/blog/unsubscribe?token=' . $subscriber->token, ['List-Unsubscribe' => 'One-Click']);
        $I->seeResponseCodeIs(200);
        $I->assertSame(BlogSubscriber::STATUS_UNSUBSCRIBED, $subscriber->refresh() ? $subscriber->status : null);

        // An old confirmation link does not put an unsubscribed reader back.
        $I->amOnPage('/blog/confirm?token=' . $subscriber->token);
        $I->see('This link has expired');
        $I->dontSee('Yes, subscribe me');
        $I->assertSame(BlogSubscriber::STATUS_UNSUBSCRIBED, $subscriber->refresh() ? $subscriber->status : null);

        $I->amOnPage('/blog/confirm?token=wrong');
        $I->seeResponseCodeIs(404);
    }

    public function anOldAddressLeadsToTheMovedPostAndTrashIsGone(FunctionalTester $I): void
    {
        $id = (int) $I->haveRecord(BlogPost::class, ['title' => 'New home', 'slug' => 'new-home', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60, 'created_at' => time(), 'updated_at' => time()]);
        $I->haveRecord(\common\models\BlogPostRedirect::class, ['old_slug' => 'old-home', 'post_id' => $id, 'created_at' => time()]);
        $I->stopFollowingRedirects();
        $I->amOnPage('/blog/old-home');
        $I->seeResponseCodeIs(301);
        $I->startFollowingRedirects();
        $I->amOnPage('/blog/old-home');
        $I->seeCurrentUrlEquals('/index-test.php/blog/new-home');
        $I->see('New home', 'h1');

        BlogPost::updateAll(['deleted_at' => time()], ['id' => $id]);
        foreach (['/blog/new-home', '/blog/old-home'] as $gone) {
            $I->amOnPage($gone);
            $I->seeResponseCodeIs(404);
        }
        $I->amOnPage('/sitemap.xml');
        $I->dontSeeInSource('new-home');
    }
}
