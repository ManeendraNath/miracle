<?php

declare(strict_types=1);

namespace common\services;

use common\components\CustomYii;
use Yii;
use yii\httpclient\Client;

/**
 * Tells Bing (and the other IndexNow search engines) straight away when a page is added, changed or removed, instead
 * of waiting for the next crawl. Google doesn't use IndexNow; it reads the sitemap.
 * Addresses are collected during the request and sent once, after the response (or at the end of a console run).
 * Only on the live site (production, https address): nothing is sent from local copies or tests.
 */
final class IndexNowService
{
    private const ENDPOINT = 'https://api.indexnow.org/indexnow';

    /** @var array<string, true> */
    private static array $_queued = [];

    /** The site's key, made by the migration. Published at /indexnow-key.txt. */
    public static function key(): string
    {
        return (string) CustomYii::getSetting('indexnow_key');
    }

    /** @param string $path e.g. /blog/my-post */
    public static function queue(string $path): void
    {
        $base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');
        // Production only: tests and local copies may have the live address in .env.
        if (!YII_ENV_PROD || !str_starts_with($base, 'https://') || self::key() === '') {
            return;
        }
        if (self::$_queued === []) {
            $app = Yii::$app;
            if ($app instanceof \yii\web\Application) {
                $app->response->on(\yii\web\Response::EVENT_AFTER_SEND, [self::class, 'send']);
            } else {
                $app->on(\yii\base\Application::EVENT_AFTER_REQUEST, [self::class, 'send']);
            }
        }
        self::$_queued[$base . $path] = true;
    }

    public static function send(): void
    {
        $urls = array_keys(self::$_queued);
        self::$_queued = [];
        if ($urls === []) {
            return;
        }
        // The visitor or staff member doesn't wait for this: the session is saved and released first (its lock
        // would hold the next page), then the response is finished where the server allows it.
        if (Yii::$app->has('session', true)) {
            Yii::$app->session->close();
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();
        }
        try {
            $base = rtrim((string) Yii::$app->params['frontendBaseUrl'], '/');
            $response = (new Client(['transport' => \yii\httpclient\StreamTransport::class]))->post(self::ENDPOINT, [
                'host' => (string) parse_url($base, PHP_URL_HOST),
                'key' => self::key(),
                'keyLocation' => $base . '/indexnow-key.txt',
                'urlList' => $urls,
            ])->setFormat(Client::FORMAT_JSON)->setOptions(['timeout' => 5, 'sslVerifyPeer' => true])->send();
            // 200 and 202 are both accepted; anything else is only logged: the sitemap still has the page.
            if (!in_array((int) $response->statusCode, [200, 202], true)) {
                Yii::warning(['message' => 'IndexNow refused the addresses.', 'status' => $response->statusCode, 'urls' => $urls], __METHOD__);
            }
        } catch (\Throwable $e) {
            Yii::warning(['message' => 'IndexNow could not be reached.', 'error' => $e->getMessage()], __METHOD__);
        }
    }
}
