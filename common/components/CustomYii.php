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

    public static function powered()
    {
        echo "Powered by <a href=''>Miracle Web Technologies</a>";
    }
}
