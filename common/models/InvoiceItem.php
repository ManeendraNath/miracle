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
}
