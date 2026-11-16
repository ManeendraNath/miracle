<?php

declare(strict_types=1);

namespace common\models;

use Yii;
use yii\web\Request;

/** One visitor reading one blog post on one day. */
final class BlogPostView extends base\BlogPostView
{
    /** Crawlers, link previews and scripts: not readers. ("cu"bot: CUBOT phones are readers.) */
    private const NOT_A_READER = '/(?<!cu)bot|whatsapp|telegram|skype|crawl|spider|slurp|preview|fetch|scan|monitor|headless|lighthouse|facebookexternalhit|embedly|curl|wget|python|java\/|go-http|okhttp|libwww|httpclient/i';

    /** Counts a read of $post, once per visitor a day. The visitor hash changes daily and keeps no address. */
    public static function record(BlogPost $post, Request $request): void
    {
        $agent = (string) $request->userAgent;
        $purpose = strtolower((string) ($request->headers->get('Sec-Purpose') ?? $request->headers->get('Purpose')));
        if (!$request->isGet || $agent === '' || preg_match(self::NOT_A_READER, $agent) === 1 || str_contains($purpose, 'prefetch')) {
            return;
        }
        $day = date('Y-m-d');
        $visitor = md5($request->cookieValidationKey . '|' . $day . '|' . $request->userIP . '|' . $agent);
        try {
            Yii::$app->db->createCommand('INSERT IGNORE INTO {{%blog_post_view}} (post_id, day, visitor) VALUES (:post, :day, :visitor)', [
                ':post' => (int) $post->id, ':day' => $day, ':visitor' => $visitor,
            ])->execute();
        } catch (\Throwable $e) {
            // A count is never worth a broken page.
            Yii::warning(['message' => 'Blog view not counted.', 'postId' => $post->id, 'error' => $e->getMessage()], __METHOD__);
        }
    }

    /**
     * Views of each post: all time and the last 30 days.
     *
     * @param int[] $postIds
     * @return array<int, array{total: int, recent: int}>
     */
    public static function counts(array $postIds): array
    {
        if ($postIds === []) {
            return [];
        }
        $rows = self::find()->select(['post_id', 'total' => 'COUNT(*)', 'recent' => 'SUM(day >= :since)'])
            ->where(['post_id' => $postIds])->groupBy('post_id')->addParams([':since' => date('Y-m-d', strtotime('-29 days'))])
            ->asArray()->all();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['post_id']] = ['total' => (int) $row['total'], 'recent' => (int) $row['recent']];
        }

        return $counts;
    }
}
