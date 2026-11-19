<?php

declare(strict_types=1);

namespace common\services;

use common\models\ProductOrder;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\CurlTransport;

/**
 * Licences of software we sell by subscription (Product::KIND_SOFTWARE), from both ends:
 *
 * - On our site, status() answers an installed copy's daily check (POST /licence/check).
 * - On a copy sold to a business (PORTAL_ONLY), check() asks our site and remembers the answer for the admin
 *   banner (current()). A lapsed licence never stops the copy: its customers' billing must go on. The banner says
 *   updates and support have stopped.
 */
final class LicenceService
{
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const WRONG_SITE = 'wrong_site';
    public const UNKNOWN = 'unknown';
    /** The copy has no key, or has never reached our site. */
    public const NOT_CHECKED = 'not_checked';

    /** Shared by the console (cron writes) and the admin (banner reads): each app has its own @runtime. */
    private const FILE = '@common/runtime/licence.json';

    /** @return array{status: string, until: null, product: null} the answer when nothing about the key is told */
    public static function unknown(): array
    {
        return ['status' => self::UNKNOWN, 'until' => null, 'product' => null];
    }

    /**
     * Our side: what a key is worth on a site.
     *
     * @return array{status: string, until: ?string, product: ?string}
     */
    public static function status(string $key, string $site): array
    {
        $order = ProductOrder::find()->with(['product', 'maintenance', 'invoice'])->where(['licence_key' => strtoupper(trim($key))])->one();
        if ($order === null || !$order->product->isSoftware()) {
            return self::unknown();
        }
        // The address first: a key used elsewhere tells nothing more about its subscription (and is no check-in).
        if ($order->licence_site !== null && ProductOrder::hostOf($site) !== $order->licence_site) {
            return ['status' => self::WRONG_SITE, 'until' => null, 'product' => null];
        }
        // The licensed copy checked in. At most one write a day: checking more often costs nothing.
        if ((int) $order->licence_checked_at < time() - 72000) { // 20 hours: a daily check a little early still counts
            $order->updateAttributes(['licence_checked_at' => time()]);
        }
        $until = $order->maintenance?->current_expiry_date;

        return ['status' => $order->licenceActive() ? self::ACTIVE : self::EXPIRED, 'until' => $until !== null ? (string) $until : null, 'product' => $order->product->name];
    }

    /** The copy's side, run daily by cron/licence-check: asks our site and keeps the answer. */
    public static function check(?\yii\httpclient\Transport $transport = null): string
    {
        $key = (string) (Yii::$app->params['licence']['key'] ?? '');
        $server = rtrim((string) (Yii::$app->params['licence']['server'] ?? ''), '/');
        if ($key === '' || $server === '') {
            self::write(['status' => self::NOT_CHECKED, 'until' => null, 'product' => null, 'checked_at' => null, 'reason' => 'No licence key is set (LICENCE_KEY in .env).']);

            return 'No licence key is set.';
        }
        if (trim((string) (Yii::$app->params['frontendBaseUrl'] ?? '')) === '') {
            self::write(['status' => self::NOT_CHECKED, 'until' => null, 'product' => null, 'checked_at' => null, 'reason' => 'FRONTEND_BASE_URL is not set in .env, so the licence cannot be checked for this address.']);

            return 'FRONTEND_BASE_URL is not set.';
        }
        try {
            $response = (new Client(['transport' => $transport ?? CurlTransport::class]))->post($server . '/licence/check', ['key' => $key, 'site' => (string) Yii::$app->params['frontendBaseUrl']])
                ->setOptions(['timeout' => 20])->send();
            $data = $response->isOk ? $response->data : null;
        } catch (\Throwable $e) {
            $data = null;
            Yii::warning(['message' => 'Licence check failed.', 'error' => $e->getMessage()], __METHOD__);
        }
        if (!is_array($data) || !in_array($data['status'] ?? null, [self::ACTIVE, self::EXPIRED, self::WRONG_SITE, self::UNKNOWN], true)) {
            // Our site is down or unreachable: the last answer stands, so a passing outage changes nothing; the
            // banner speaks up once it has lasted (failed_at with no recent answer).
            $last = self::current();
            // A local problem from an earlier run (no key) is not why this one failed.
            if ($last['status'] === self::NOT_CHECKED) {
                $last['reason'] = 'The licence server could not be reached yet. The check runs daily and tries again.';
            } else {
                unset($last['reason']);
            }
            self::write(['failing_since' => $last['failing_since'] ?? time()] + $last);

            return 'The licence server could not be reached; the last result is kept.';
        }
        self::save(['status' => $data['status'], 'until' => is_string($data['until'] ?? null) ? $data['until'] : null, 'product' => is_string($data['product'] ?? null) ? $data['product'] : null]);

        return 'Licence: ' . $data['status'] . '.';
    }

    /**
     * The last answer, for the admin banner. checked_at is when our site last answered; failing_since when the checks since then began to fail.
     *
     * @return array{status: string, until: ?string, product: ?string, checked_at: ?int, failing_since?: int, reason?: string}
     */
    public static function current(): array
    {
        $file = Yii::getAlias(self::FILE);
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) && isset($data['status']) ? $data + ['until' => null, 'product' => null, 'checked_at' => null]
            : ['status' => self::NOT_CHECKED, 'until' => null, 'product' => null, 'checked_at' => null];
    }

    /** @param array<string, mixed> $data an answer (or a local reason): replaces the last one */
    private static function save(array $data): void
    {
        self::write($data + ['checked_at' => time()]);
    }

    /** @param array<string, mixed> $data */
    private static function write(array $data): void
    {
        $file = Yii::getAlias(self::FILE);
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
