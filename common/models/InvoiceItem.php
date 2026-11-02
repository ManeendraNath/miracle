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

    public static function getTypes(): array
    {
        return [
            self::TYPE_DOMAIN => 'Domain',
            self::TYPE_HOSTING => 'Hosting',
            self::TYPE_MAINTENANCE => 'Maintenance',
            self::TYPE_CUSTOM => 'Custom',
            self::TYPE_ARREARS => 'Previous Outstanding',
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
