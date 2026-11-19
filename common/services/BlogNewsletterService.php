<?php

declare(strict_types=1);

namespace common\services;

use common\models\BlogPost;
use common\models\BlogSubscriber;
use Yii;
use yii\db\Query;

/**
 * Confirmation emails for new subscribers, and new posts emailed to subscribers by the cron job.
 * A post is sent once, when it is first live and indexable, to those subscribed by then; posts set not to be
 * emailed (or kept out of search engines) are marked done without sending.
 */
final class BlogNewsletterService
{

    /** Failed emails in a row that mean the mail server itself is down: the run stops, the next one carries on. */
    private const FAILURES_TO_STOP = 3;

    public static function sendConfirmation(BlogSubscriber $subscriber): bool
    {
        $site = self::siteName();
        return Yii::$app->mailer->compose(
                                ['html' => 'blog-subscribe-confirm-html', 'text' => 'blog-subscribe-confirm-text'],
                                ['site' => $site, 'confirmUrl' => $subscriber->emailLink('confirm')]
                        )
                        ->setTo($subscriber->email)
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                        ->setSubject('Confirm your subscription to the ' . $site . ' blog')
                        ->send();
    }

    /**
     * Sends up to $limit emails, oldest post first. Returns how many went out. An address the mail server refuses is
     * recorded as failed and skipped for that post; FAILURES_TO_STOP in a row stop the run with an error.
     * ponytail: an email sent just before a crash, without its delivery row, goes out again on the next run.
     */
    public static function sendBatch(int $limit): int
    {
        self::startNewPosts();
        $sent = 0;
        $tried = 0;
        $failedInARow = [];
        $posts = BlogPost::findPublic()->andWhere(['not', ['newsletter_started_at' => null]])->andWhere(['newsletter_done_at' => null])
                        ->orderBy(['newsletter_started_at' => SORT_ASC, 'id' => SORT_ASC])->all();
        foreach ($posts as $post) {
            // Switched off, or kept out of search engines, while it was being sent: the rest are not sent.
            if (!$post->send_newsletter || $post->noindex) {
                $post->updateAttributes(['newsletter_done_at' => time()]);
                continue;
            }
            $subscribers = BlogSubscriber::find()
                            ->where(['status' => BlogSubscriber::STATUS_SUBSCRIBED])
                            ->andWhere(['<=', 'confirmed_at', $post->newsletter_started_at])
                            ->andWhere(['not in', 'id', (new Query())->select('subscriber_id')->from('{{%blog_newsletter_delivery}}')->where(['post_id' => $post->id])])
                            ->orderBy(['id' => SORT_ASC])->limit($limit - $tried)->all();
            foreach ($subscribers as $subscriber) {
                $ok = self::sendPost($post, $subscriber);
                $tried++;
                Yii::$app->db->createCommand()->insert('{{%blog_newsletter_delivery}}', ['post_id' => $post->id, 'subscriber_id' => $subscriber->id, 'sent_at' => time(), 'failed' => $ok ? 0 : 1])->execute();
                if ($ok) {
                    $sent++;
                    $failedInARow = [];
                    continue;
                }
                $failedInARow[] = $subscriber->id;
                if (count($failedInARow) >= self::FAILURES_TO_STOP) {
                    // The server, not these addresses: they are tried again next run.
                    Yii::$app->db->createCommand()->delete('{{%blog_newsletter_delivery}}', ['post_id' => $post->id, 'subscriber_id' => $failedInARow])->execute();
                    throw new \RuntimeException("Sent {$sent} newsletter email(s), then " . self::FAILURES_TO_STOP . ' in a row failed: the mail server may be down. The next run tries them again.');
                }
            }
            if ($tried >= $limit) {
                break;
            }
            $post->updateAttributes(['newsletter_done_at' => time()]);
        }

        return $sent;
    }

    /**
     * Live posts not yet looked at: queued to send, or marked done when they are not to be emailed. A post that goes
     * live kept out of search engines is not emailed later either: by then it is no longer new.
     */
    private static function startNewPosts(): void
    {
        foreach (BlogPost::findPublic()->andWhere(['newsletter_started_at' => null])->all() as $post) {
            $send = $post->send_newsletter && !$post->noindex;
            $post->updateAttributes(['newsletter_started_at' => time(), 'newsletter_done_at' => $send ? null : time()]);
            // Just went live (e.g. a scheduled post): Bing hears now, not at its next crawl.
            if (!$post->noindex) {
                \common\services\IndexNowService::queue('/blog/' . $post->slug);
            }
        }
    }

    private static function sendPost(BlogPost $post, BlogSubscriber $subscriber): bool
    {
        $base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
        $unsubscribe = $subscriber->emailLink('unsubscribe');
        $message = Yii::$app->mailer->compose(['html' => 'blog-newsletter-html', 'text' => 'blog-newsletter-text'], [
                    'post' => $post,
                    'postUrl' => $base . '/blog/' . $post->slug,
                    'coverUrl' => $post->cover_image ? $base . '/' . $post->cover_image : null,
                    'unsubscribeUrl' => $unsubscribe,
                    'site' => self::siteName(),
                ])
                ->setTo($subscriber->email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject($post->title);
        // One-click unsubscribe from the mail app itself (Gmail and Yahoo expect it from bulk senders).
        if ($message instanceof \yii\symfonymailer\Message) {
            $message->addHeader('List-Unsubscribe', '<' . $unsubscribe . '>');
            $message->addHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        return $message->send();
    }

    private static function siteName(): string
    {
        return \common\components\CustomYii::businessName();
    }
}
