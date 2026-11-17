<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\models\BlogComment;
use common\models\BlogPost;
use common\models\BlogPostRedirect;
use common\models\BlogTag;
use common\services\BlogImageService;
use yii\web\UploadedFile;

/** What a blog post keeps from the editor, and when visitors can see it. */
final class BlogPostTest extends Unit
{
    public function testBodyKeepsArticleMarkupOnly(): void
    {
        $clean = BlogPost::cleanBody('<h2>Title</h2><p onclick="steal()">Text <a href="javascript:alert(1)">bad</a> <a href="https://example.com" target="_blank">good</a></p><script>alert(1)</script><iframe src="https://evil.test"></iframe><img src="https://example.com/a.png" onerror="x()"><ul><li>One</li></ul>');

        $this->assertStringContainsString('<h2>Title</h2>', $clean);
        $this->assertStringContainsString('<ul><li>One</li></ul>', $clean);
        $this->assertStringContainsString('href="https://example.com"', $clean);
        $this->assertStringContainsString('rel="noreferrer noopener"', $clean);
        $this->assertStringContainsString('<img src="https://example.com/a.png" alt="" />', $clean);
        foreach (['onclick', 'javascript:', '<script', 'alert(1)', '<iframe', 'onerror'] as $removed) {
            $this->assertStringNotContainsString($removed, $clean);
        }
    }

    public function testAnEmptyEditorCountsAsNoText(): void
    {
        $this->assertSame('', BlogPost::cleanBody('<p><br></p>'));
        $post = new BlogPost(['title' => 'Hello there', 'summary' => 'S', 'body' => '<p><br></p>', 'status' => BlogPost::STATUS_DRAFT]);
        $this->assertFalse($post->validate());
        $this->assertArrayHasKey('body', $post->errors);
        $this->assertSame('hello-there', $post->slug);
    }

    public function testStateFollowsStatusAndPublishTime(): void
    {
        $this->assertSame('Draft', (new BlogPost(['status' => BlogPost::STATUS_DRAFT, 'published_at' => time() - 60]))->stateLabel());
        $this->assertSame('Scheduled', (new BlogPost(['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() + 3600]))->stateLabel());
        $this->assertSame('Published', (new BlogPost(['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() - 60]))->stateLabel());
    }

    public function testAFileThatIsNotAnImageIsRefused(): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'blog');
        file_put_contents($path, '<?php echo 1;');
        $file = new UploadedFile(['name' => 'photo.png', 'tempName' => $path, 'type' => 'image/png', 'size' => 13, 'error' => UPLOAD_ERR_OK]);
        try {
            (new BlogImageService())->store($file);
            $this->fail('A PHP file passed as an image.');
        } catch (\DomainException $e) {
            $this->assertSame('Use a JPG, PNG or WebP image.', $e->getMessage());
        } finally {
            unlink($path);
        }
    }

    public function testTheFeedAddressIsReserved(): void
    {
        $post = new BlogPost(['title' => 'Feed']);
        $post->validate(['slug']);
        $this->assertSame('feed', $post->slug);
        $this->assertTrue($post->hasErrors('slug'));
    }

    public function testAFormCannotSetTheCoverAuthorOrPublishTime(): void
    {
        $post = new BlogPost(['cover_image' => 'uploads/blog/2026-11/a.jpg', 'author_id' => 1, 'published_at' => 100]);
        // The cover file input always posts an empty cover_image next to the file.
        $post->load(['BlogPost' => ['title' => 'T', 'cover_image' => '', 'author_id' => 2, 'published_at' => 200]]);
        $this->assertSame('T', $post->title);
        $this->assertSame(['uploads/blog/2026-11/a.jpg', 1, 100], [$post->cover_image, $post->author_id, $post->published_at]);
    }

    public function testClearingPublishOnPublishesAScheduledPostNow(): void
    {
        $post = new BlogPost(['status' => BlogPost::STATUS_PUBLISHED, 'published_at' => time() + 86400, 'publishAt' => '']);
        $post->beforeSave(false);
        $this->assertLessThanOrEqual(time(), $post->published_at);
    }

    public function testTagsAreSharedAndAnEditKeepsTheVersionBefore(): void
    {
        $db = \Yii::$app->db->beginTransaction();
        try {
            $post = new BlogPost(['title' => 'Tags and versions ' . uniqid(), 'summary' => 'First summary', 'body' => '<p>First text</p>', 'status' => BlogPost::STATUS_DRAFT, 'tagNames' => 'Web apps, Costs, web apps']);
            $this->assertTrue($post->save(), json_encode($post->errors));
            $this->assertSame(['Costs', 'Web apps'], array_map(static fn($tag): string => $tag->name, $post->tags), 'one tag per slug, first spelling kept');
            $this->assertSame([], $post->revisions, 'a new post has no earlier version');

            $other = new BlogPost(['title' => 'Other ' . uniqid(), 'summary' => 'S', 'body' => '<p>T</p>', 'status' => BlogPost::STATUS_DRAFT, 'tagNames' => 'costs']);
            $this->assertTrue($other->save());
            $this->assertSame(1, (int) BlogTag::find()->where(['slug' => 'costs'])->count(), 'an existing tag is reused');

            $post = BlogPost::findOne($post->id);
            $post->tagNames = null; // not on the form: tags stay
            $post->body = '<p>Second text</p>';
            $this->assertTrue($post->save());
            $post->setAttributes(['is_featured' => 1], false);
            $this->assertTrue($post->save(), 'a change to settings only keeps no version');
            $post = BlogPost::findOne($post->id);
            $this->assertCount(2, $post->tags);
            $this->assertCount(1, $post->revisions);
            $this->assertSame(['First summary', '<p>First text</p>'], [$post->revisions[0]->summary, $post->revisions[0]->body]);

            $post->tagNames = '';
            $this->assertTrue($post->save());
            $this->assertSame([], BlogPost::findOne($post->id)->tags, 'clearing the field removes the tags');
        } finally {
            $db->rollBack();
        }
    }

    public function testAFormCannotSetTheAuthorPhoto(): void
    {
        $admin = new \common\models\Admin(['photo' => 'uploads/blog/a.jpg']);
        $admin->load(['Admin' => ['photo' => 'x.jpg', 'display_name' => 'Name']]);
        $this->assertSame(['uploads/blog/a.jpg', 'Name'], [$admin->photo, $admin->display_name]);
    }

    public function testTagNamesNeedLettersAndKeepSymbolsApart(): void
    {
        $this->assertSame(['c-plus-plus', 'c-sharp', 'c'], [BlogTag::slugFor('C++'), BlogTag::slugFor('C#'), BlogTag::slugFor('C')]);
        $post = new BlogPost(['tagNames' => 'SEO, !!!']);
        $post->validate(['tagNames']);
        $this->assertStringContainsString('Use letters or digits', (string) $post->getFirstError('tagNames'));
    }

    public function testHeadingsGetAnchorsAndAnFaqSectionIsRead(): void
    {
        $post = new BlogPost(['body' => '<h2>Intro</h2><p>Hé — text</p><h2>Frequently asked questions</h2><h3>How much?</h3><p>About <strong>$500</strong>.</p><ul><li>one</li><li>two</li></ul><h3>How long?</h3><p>Two weeks.</p><h2>Intro</h2><p>x</p>']);
        $out = $post->rendered();

        $this->assertSame(['h-intro', 'h-frequently-asked-questions', 'h-how-much', 'h-how-long', 'h-intro-2'], array_column($out['toc'], 'id'));
        $this->assertSame([2, 2, 3, 3, 2], array_column($out['toc'], 'level'));
        $this->assertSame([['question' => 'How much?', 'answer' => 'About $500. one two'], ['question' => 'How long?', 'answer' => 'Two weeks.']], $out['faq']);
        $this->assertStringContainsString('<h2 id="h-intro">Intro</h2><p>Hé — text</p>', $out['html']);
        $this->assertStringContainsString('<h2 id="h-intro-2">Intro</h2>', $out['html']);
        $this->assertStringNotContainsString('id="post"', $out['html']);
    }

    public function testOnlySpamOlderThanThirtyDaysIsDeleted(): void
    {
        $post = new BlogPost(['title' => 'Spam target', 'slug' => 'spam-target', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_DRAFT]);
        $this->assertTrue($post->save());
        $old = time() - (BlogComment::SPAM_DAYS + 1) * 86400;
        $rows = [
            'old spam' => [BlogComment::STATUS_SPAM, $old],
            'new spam' => [BlogComment::STATUS_SPAM, time()],
            'old shown' => [BlogComment::STATUS_APPROVED, $old],
            'old waiting' => [BlogComment::STATUS_PENDING, $old],
        ];
        $db = \Yii::$app->db;
        foreach ($rows as $name => [$status, $at]) {
            $db->createCommand()->insert('{{%blog_comment}}', ['post_id' => $post->id, 'author_name' => $name, 'body' => 'x', 'status' => $status, 'created_at' => $at, 'updated_at' => $at])->execute();
        }
        // Sent long ago, marked Spam by staff today: its 30 days start now.
        $db->createCommand()->insert('{{%blog_comment}}', ['post_id' => $post->id, 'author_name' => 'marked today', 'body' => 'x', 'status' => BlogComment::STATUS_SPAM, 'created_at' => $old, 'updated_at' => time()])->execute();
        $oldSpam = (int) BlogComment::find()->select('id')->where(['author_name' => 'old spam', 'post_id' => $post->id])->scalar();
        $db->createCommand()->insert('{{%blog_comment}}', ['post_id' => $post->id, 'parent_id' => $oldSpam, 'author_name' => 'reply to old spam', 'body' => 'x', 'status' => BlogComment::STATUS_APPROVED, 'created_at' => time(), 'updated_at' => time()])->execute();

        BlogComment::deleteOldSpam();
        $left = BlogComment::find()->select('author_name')->where(['post_id' => $post->id])->orderBy('author_name')->column();
        $this->assertSame(['marked today', 'new spam', 'old shown', 'old waiting'], $left);
    }

    public function testAMovedLivePostKeepsItsOldAddressAndADraftDoesNot(): void
    {
        $post = new BlogPost(['title' => 'Moving post', 'slug' => 'moving-post', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_PUBLISHED]);
        $this->assertTrue($post->save());
        $post->slug = 'moved-post';
        $this->assertTrue($post->save());
        $this->assertSame((int) $post->id, (int) BlogPostRedirect::find()->select('post_id')->where(['old_slug' => 'moving-post'])->scalar());

        // Moved back and rescheduled: it was live, so the address it leaves still leads to it.
        $post->slug = 'moving-post';
        $post->publishAt = date('Y-m-d\TH:i', time() + 86400);
        $this->assertTrue($post->save());
        $this->assertTrue(BlogPostRedirect::find()->where(['old_slug' => 'moved-post', 'post_id' => $post->id])->exists());

        // Another post going live at "moved-post" takes the address over from the old redirect.
        $other = new BlogPost(['title' => 'Other', 'slug' => 'moved-post', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_DRAFT]);
        $this->assertTrue($other->save());
        $this->assertTrue(BlogPostRedirect::find()->where(['old_slug' => 'moved-post'])->exists(), 'not while it is a Draft');
        $other->status = BlogPost::STATUS_PUBLISHED;
        $this->assertTrue($other->save());
        $this->assertFalse(BlogPostRedirect::find()->where(['old_slug' => 'moved-post'])->exists());

        $draft = new BlogPost(['title' => 'Draft move', 'slug' => 'draft-move', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_DRAFT]);
        $this->assertTrue($draft->save());
        $draft->slug = 'draft-moved';
        $this->assertTrue($draft->save());
        $this->assertFalse(BlogPostRedirect::find()->where(['old_slug' => 'draft-move'])->exists());
    }

    public function testTrashHidesAPostAndOldTrashIsEmptied(): void
    {
        $post = new BlogPost(['title' => 'Binned', 'slug' => 'binned', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'is_featured' => 1]);
        $this->assertTrue($post->save());
        $post->trash();
        $this->assertFalse($post->isPublic());
        $this->assertSame('In trash', $post->stateLabel());
        $this->assertFalse(BlogPost::findPublic()->andWhere(['id' => $post->id])->exists());
        $taken = new BlogPost(['title' => 'Binned', 'slug' => 'binned', 'summary' => 'S.', 'body' => '<p>B.</p>']);
        $this->assertFalse($taken->validate(['slug']));
        $this->assertStringContainsString('in the trash', $taken->getFirstError('slug'));
        $post->untrash();
        $this->assertTrue($post->isPublic());

        // A Draft restored days later is still emailed once it is published.
        $draft = new BlogPost(['title' => 'Binned draft', 'slug' => 'binned-draft', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_DRAFT]);
        $this->assertTrue($draft->save());
        $draft->trash();
        $draft->updateAttributes(['deleted_at' => time() - 3 * 86400]);
        $draft->untrash();
        $this->assertNull($draft->newsletter_started_at);

        $post->updateAttributes(['deleted_at' => time() - (BlogPost::TRASH_DAYS + 1) * 86400]);
        $this->assertSame(1, BlogPost::emptyOldTrash());
        $this->assertNull(BlogPost::findOne($post->id));
    }

    public function testADuplicateIsADraftWithItsOwnAddressAndTheSameTags(): void
    {
        $post = new BlogPost(['title' => 'Original', 'slug' => 'original', 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_PUBLISHED, 'tagNames' => 'SEO, Yii']);
        $this->assertTrue($post->save());
        $copy = $post->duplicate(null);
        $second = $post->duplicate(null);
        $this->assertSame('Copy of Original', $copy->title);
        $this->assertSame('original-copy', $copy->slug);
        $this->assertSame('original-copy-2', $second->slug);
        $this->assertSame(BlogPost::STATUS_DRAFT, (int) $copy->status);
        $this->assertSame('SEO, Yii', BlogPost::findOne($copy->id)->tagNamesText());
    }
}
