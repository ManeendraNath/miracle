<?php

namespace common\components;

use Yii;
use common\models\SiteSetting;

/**
 * CustomYii serves as the localized global utility wrapper for miraclewebtechnologies.com
 */
class CustomYii extends \Yii
{
    /**
     * @var string the application name.
     */
    const APPLICATION_NAME = 'Miracle Web Technologies';

    /**
     * On a copy, the app name (Yii::$app->name: checkout titles, sign-in emails, the two-factor label) is the
     * business name in Site Settings, so a rename there reaches them too. Run before each request.
     */
    public static function useBusinessName(): void
    {
        if (PortalOnly::enabled()) {
            Yii::$app->name = self::businessName();
        }
    }

    /** Settings keep phone numbers as digits with the country code: shown as +91 96545 11842. */
    public static function formatPhone(string $digits): string
    {
        return preg_match('/^91(\d{5})(\d{5})$/', $digits, $m) === 1 ? "+91 {$m[1]} {$m[2]}" : '+' . $digits;
    }

    /**
     * @var SiteSetting|null Internal memory cache store for the site layout records
     */
    private static $_settings = null;

    /**
     * Fetches, caches, and exposes the global database branding parameters globally.
     * Bypasses duplicate database queries on extensive nested page loads.
     *
     * @param string|null $attribute Explicit column property name to fetch (e.g., 'email_1', 'mobile_1')
     * @param mixed $default Fallback value if database parameter returns null
     * @return mixed
     */
    public static function getSetting(?string $attribute = null, mixed $default = '')
    {
        if (self::$_settings === null) {
            // Automatically searches row ID 1 which matches your structural schema seeder
            self::$_settings = SiteSetting::findOne(1);

            // Safe fallback initialization to prevent application crashes if database is blank
            if (self::$_settings === null) {
                self::$_settings = new SiteSetting();
            }
        }

        // If an explicit column key is parsed, extract its row cell value directly
        if ($attribute !== null) {
            return self::$_settings->hasAttribute($attribute) && !empty(self::$_settings->$attribute) ? self::$_settings->$attribute : $default;
        }

        // Otherwise return the complete active ActiveRecord database record structure object
        return self::$_settings;
    }

    /** The business name (Site Settings), with the original as the fallback while none is set. */
    public static function businessName(): string
    {
        return (string) (self::getSetting('site_name') ?: self::APPLICATION_NAME);
    }

    /** The business's website (Site Settings), else this portal's address; no trailing slash. */
    public static function businessUrl(): string
    {
        return rtrim((string) (self::getSetting('site_url') ?: self::portalUrl()), '/');
    }

    /** Where this portal is served (FRONTEND_BASE_URL), for links and images in emails; else the business's website. */
    public static function portalUrl(): string
    {
        return rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? '') ?: (string) self::getSetting('site_url'), '/');
    }

    /**
     * The logo to show: ours ($ours, a URL) on our own site. On a copy sold to another business (PORTAL_ONLY), theirs
     * in frontend/web/brand/, which no update overwrites: logo.png, and for dark backgrounds logo-white.png. Null when
     * there is none, and the name is shown instead. ?v= makes a replaced file show at once.
     */
    public static function logoUrl(string $ours, bool $dark = false): ?string
    {
        // On dark only the light version: a usual dark logo would not be seen there, so the name shows instead.
        return PortalOnly::enabled() ? self::brandUrl([$dark ? 'logo-white.png' : 'logo.png']) : $ours;
    }

    /** As logoUrl(), as a file path (PDFs read the file). */
    public static function logoFile(string $ours): ?string
    {
        return PortalOnly::enabled() ? self::brandFile(['logo.png']) : $ours;
    }

    /**
     * The logo as an <img> (alt: the business name), or without one the name in a $tag; nothing when $tag is null
     * (where the name is shown next to it anyway).
     *
     * @param array<string, mixed> $options
     * @param array<string, mixed> $tagOptions
     */
    public static function logoImg(string $ours, array $options = [], bool $dark = false, ?string $tag = 'span', array $tagOptions = []): string
    {
        $url = self::logoUrl($ours, $dark);
        if ($url !== null) {
            return \yii\helpers\Html::img($url, $options + ['alt' => self::businessName()]);
        }

        return $tag === null ? '' : \yii\helpers\Html::tag($tag, \yii\helpers\Html::encode(self::businessName()), $tagOptions);
    }

    /**
     * What the business does, under its name on invoices: on our own site our fixed line (as invoices always said),
     * on a copy its Site Settings line. Empty means none.
     */
    public static function invoiceServicesLine(): string
    {
        return PortalOnly::enabled() ? trim((string) self::getSetting('services_line')) : 'Website development, hosting & maintenance';
    }

    /**
     * The browser icon: ours on our site; on a copy brand/favicon.ico if they add one, else none linked (the package
     * has a blank favicon.ico for the browsers that ask anyway).
     */
    public static function faviconUrl(string $ours): ?string
    {
        return PortalOnly::enabled() ? self::brandUrl(['favicon.ico']) : $ours;
    }

    /** @param string[] $names first one that exists */
    private static function brandUrl(array $names): ?string
    {
        $file = self::brandFile($names);
        // Where frontend/web is served (FRONTEND_BASE_URL), never the business's own website: brand/ is not there.
        $base = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? ''), '/');

        return $file !== null && $base !== '' ? $base . '/brand/' . basename($file) . '?v=' . filemtime($file) : null;
    }

    /** @param string[] $names */
    private static function brandFile(array $names): ?string
    {
        foreach ($names as $name) {
            $file = (string) Yii::getAlias('@frontend/web/brand/' . $name);
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    public static function powered()
    {
        echo 'Powered by ' . \yii\helpers\Html::encode(self::businessName());
    }
}
