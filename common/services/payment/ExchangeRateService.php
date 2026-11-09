<?php

declare(strict_types=1);

namespace common\services\payment;

use common\components\CustomYii;
use common\models\SiteSetting;
use Yii;
use yii\httpclient\Client;
use yii\httpclient\StreamTransport;
use yii\httpclient\Transport;

/**
 * The rupees per US dollar PayPal charges at (Site Settings > Payments). Automatic: the European Central Bank
 * rate from Frankfurter (free, no key, working days), checked at most every 12 hours when a rate is needed, less
 * the margin that covers PayPal's own conversion. Otherwise the rate typed in by hand.
 */
final class ExchangeRateService
{
    private const URL = 'https://api.frankfurter.dev/v1/latest?base=USD&symbols=INR';
    private const REFRESH_SECONDS = 12 * 3600;
    /** A market rate older than this (the API down for days) is not used. */
    private const MAX_AGE_SECONDS = 4 * 86400;
    /** A new rate this far from the last good one is refused as a bad reply. */
    private const MAX_JUMP = 0.15;

    public function __construct(private ?Transport $_transport = null)
    {
    }

    /** 0 when there is none (no PayPal button). */
    public function usdRate(): float
    {
        $settings = CustomYii::getSetting();
        if ((bool) $settings->usd_rate_auto) {
            $market = $this->marketRate($settings);
            if ($market > 0 && (int) $settings->usd_rate_fetched_at >= time() - self::MAX_AGE_SECONDS) {
                return round($market * (1 - (float) $settings->usd_rate_margin / 100), 4);
            }
            if ($market > 0) {
                \common\services\admin\AdminAlertService::exchangeRateProblem('The daily US dollar rate has not updated since ' . date('d M Y', (int) $settings->usd_rate_fetched_at) . '.');
            }
        }

        return max(0.0, (float) $settings->usd_inr_rate);
    }

    /** The last good market rate, refreshed when checked over 12 hours ago; 0 if never fetched. */
    public function marketRate(SiteSetting $settings): float
    {
        if (!$settings->isNewRecord && (int) $settings->usd_rate_checked_at < time() - self::REFRESH_SECONDS) {
            $rate = $this->fetch();
            $last = (float) $settings->usd_market_rate;
            if ($rate !== null && $last > 0 && abs($rate - $last) / $last > self::MAX_JUMP) {
                \common\services\admin\AdminAlertService::exchangeRateProblem('The exchange rate service returned ₹' . $rate . ' per US$1, more than 15% away from the last rate (₹' . $last . '), so it was not used.');
                $rate = null;
            }
            // Stamped even on failure: a slow or down API is tried again in 12 hours, not on every page.
            $attributes = ['usd_rate_checked_at' => time()] + ($rate !== null ? ['usd_market_rate' => $rate, 'usd_rate_fetched_at' => time()] : []);
            SiteSetting::updateAll($attributes, ['id' => $settings->id]);
            $settings->setAttributes($attributes, false);
        }

        return (float) $settings->usd_market_rate;
    }

    private function fetch(): ?float
    {
        try {
            $response = (new Client(['transport' => $this->_transport ?? StreamTransport::class]))
                ->get(self::URL)->setOptions(['timeout' => 5, 'sslVerifyPeer' => true])->send();
            $rate = $response->isOk ? ($response->data['rates']['INR'] ?? null) : null;
            // A wild value (wrong currency, bad response) must never price an invoice.
            if (is_numeric($rate) && (float) $rate >= 10 && (float) $rate <= 1000) {
                return round((float) $rate, 4);
            }
            Yii::warning(['message' => 'Exchange rate API returned no usable USD/INR rate.', 'status' => $response->statusCode], __METHOD__);
        } catch (\Throwable $e) {
            Yii::warning(['message' => 'Exchange rate API could not be reached.', 'error' => $e->getMessage()], __METHOD__);
        }

        return null;
    }
}
