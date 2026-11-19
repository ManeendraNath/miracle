<?php

declare(strict_types=1);

namespace common\models;

class InvoiceItem extends base\InvoiceItem
{
    public const TYPE_DOMAIN = 1;
    public const TYPE_HOSTING = 2;
    public const TYPE_MAINTENANCE = 3;
    public const TYPE_CUSTOM = 4;
    public const TYPE_ARREARS = 5;
    /** A store licence (theme or plugin): see ProductOrder. */
    public const TYPE_PRODUCT = 6;
    /** A fixed-price service bought at /services. */
    public const TYPE_SERVICE_ORDER = 7;

    public static function getTypes(): array
    {
        return [
            self::TYPE_DOMAIN => 'Domain',
            self::TYPE_HOSTING => 'Hosting',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_CUSTOM => 'Custom',
            self::TYPE_ARREARS => 'Previous Outstanding',
            self::TYPE_PRODUCT => 'Themes & Plugins',
            self::TYPE_SERVICE_ORDER => 'Fixed-price services',
        ];
    }

    /**
     * Service types an admin can pick for a manual invoice line, and that a coupon
     * can be restricted to. Arrears lines are system-generated only.
     */
    public static function getServiceTypes(): array
    {
        return array_diff_key(self::getTypes(), [self::TYPE_ARREARS => true]);
    }
}
