<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use backend\tests\Support\UnitTester;
use Codeception\Test\Unit;
use common\models\BlogPost;
use common\models\BlogSubscriber;
use common\services\BlogNewsletterService;

/** New posts go once to those subscribed by then, in batches, and never for a post that is not to be emailed. */
final class BlogNewsletterTest extends Unit
{
    protected UnitTester $tester;

    private function post(string $slug, array $extra = []): BlogPost
    {
        $post = new BlogPost(['title' => ucfirst($slug), 'slug' => $slug, 'summary' => 'S.', 'body' => '<p>B.</p>', 'status' => BlogPost::STATUS_PUBLISHED] + $extra);
        $this->assertTrue($post->save(), implode(' ', $post->getFirstErrors()));
        $post->updateAttributes(['published_at' => time() - 60]);

        return $post;
    }

    private function subscriber(string $email, int $status, ?int $confirmedAt): BlogSubscriber
    {
        $subscriber = new BlogSubscriber(['email' => $email, 'token' => \Yii::$app->security->generateRandomString(40), 'status' => $status, 'confirmed_at' => $confirmedAt]);
        $this->assertTrue($subscriber->save());

        return $subscriber;
    }

    public function testANewPostGoesOnceToConfirmedSubscribersInBatches(): void
    {
        $this->subscriber('a@example.test', BlogSubscriber::STATUS_SUBSCRIBED, time() - 100);
        $this->subscriber('b@example.test', BlogSubscriber::STATUS_SUBSCRIBED, time() - 100);
        $this->subscriber('waiting@example.test', BlogSubscriber::STATUS_PENDING, null);
        $this->subscriber('gone@example.test', BlogSubscriber::STATUS_UNSUBSCRIBED, time() - 100);
        $post = $this->post('fresh-news');

        $this->assertSame(1, BlogNewsletterService::sendBatch(1));
        $this->assertNull($post->refresh() ? $post->newsletter_done_at : 'x');
        $this->assertSame(1, BlogNewsletterService::sendBatch(5));
        $post->refresh();
        $this->assertNotNull($post->newsletter_done_at);
        $this->assertSame(0, BlogNewsletterService::sendBatch(5));
        $this->tester->seeEmailIsSent(2);

        $mail = quoted_printable_decode((string) $this->tester->grabLastSentEmail()->toString());
        $this->assertStringContainsString('Subject: Fresh-news', $mail);
        $this->assertStringContainsString('List-Unsubscribe-Post: List-Unsubscribe=One-Click', $mail);
        $this->assertStringContainsString('/blog/unsubscribe?token=', $mail);
        $this->assertStringContainsString('/blog/fresh-news', $mail);
    }

    public function testSubscribersWhoJoinLaterAndPostsNotToBeEmailedGetNothing(): void
    {
        $this->post('switched-off', ['send_newsletter' => 0]);
        $this->post('hidden-from-search', ['noindex' => 1]);
        $this->subscriber('late@example.test', BlogSubscriber::STATUS_SUBSCRIBED, time() + 3600);
        $sending = $this->post('already-going');

        $this->assertSame(0, BlogNewsletterService::sendBatch(10));
        $this->tester->dontSeeEmailIsSent();
        foreach (['switched-off', 'hidden-from-search', 'already-going'] as $slug) {
            $this->assertNotNull(BlogPost::findOne(['slug' => $slug])->newsletter_done_at, $slug);
        }
        $this->assertNotNull(BlogPost::findOne($sending->id)->newsletter_started_at);
    }

    public function testSigningUpTwiceSendsOneConfirmationAndTellsNoOneWhoIsSubscribed(): void
    {
        $first = BlogSubscriber::signUp(' Reader@Example.test ', '203.0.113.9');
        $this->assertNotNull($first);
        $first->confirmationSent();
        $this->assertSame('reader@example.test', $first->email);
        $this->assertNull(BlogSubscriber::signUp('reader@example.test', '203.0.113.9'), 'a second confirmation within minutes');
        $first->confirm();
        $this->assertNull(BlogSubscriber::signUp('reader@example.test', null), 'already subscribed');
        $first->unsubscribe();
        $oldToken = $first->token;
        $again = BlogSubscriber::signUp('reader@example.test', null);
        // @phpstan-ignore method.impossibleType (phpstan remembers the null returned for the same address above)
        $this->assertNotNull($again);
        $this->assertSame(BlogSubscriber::STATUS_PENDING, $again->status);
        $this->assertNotSame($oldToken, $again->token, 'links in old emails stop working');
        $again->confirmationSent();
        $this->assertNull(BlogSubscriber::signUp('reader@example.test', null), 'one confirmation a day at most');
    }

    /** A mailer that refuses the given addresses. */
    private function refusing(array $addresses): void
    {
        \Yii::$app->set('mailer', new class (['viewPath' => '@common/mail', 'messageClass' => \yii\symfonymailer\Message::class, 'refuse' => $addresses]) extends \yii\mail\BaseMailer {
            public array $refuse = [];

            protected function sendMessage($message): bool
            {
                return !in_array(array_key_first((array) $message->getTo()), $this->refuse, true);
            }
        });
    }

    public function testARefusedAddressIsSkippedAndTheRestStillGetThePost(): void
    {
        foreach (['a', 'bad', 'c'] as $name) {
            $this->subscriber("$name@example.test", BlogSubscriber::STATUS_SUBSCRIBED, time() - 100);
        }
        $post = $this->post('one-refused');
        $this->refusing(['bad@example.test']);

        $this->assertSame(2, BlogNewsletterService::sendBatch(10));
        $this->assertNotNull(BlogPost::findOne($post->id)->newsletter_done_at);
        $this->assertSame(1, (int) $post->getBlogNewsletterDeliveries()->andWhere(['failed' => 1])->count());
    }

    public function testWhenTheMailServerIsDownNobodyIsMarkedFailed(): void
    {
        foreach (['a', 'b', 'c', 'd'] as $name) {
            $this->subscriber("$name@example.test", BlogSubscriber::STATUS_SUBSCRIBED, time() - 100);
        }
        $post = $this->post('server-down');
        $this->refusing(['a@example.test', 'b@example.test', 'c@example.test', 'd@example.test']);

        try {
            BlogNewsletterService::sendBatch(10);
            $this->fail('The run should stop.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('mail server may be down', $e->getMessage());
        }
        $this->assertSame(0, (int) $post->getBlogNewsletterDeliveries()->count(), 'all are tried again next run');

        $this->refusing([]);
        $this->assertSame(4, BlogNewsletterService::sendBatch(10));
    }
}
