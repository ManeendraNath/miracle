<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\models\BlogPost;
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
}
