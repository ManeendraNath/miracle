<?php

declare(strict_types=1);

namespace common\models;

class BillingSettings extends base\BillingSettings
{
    public static function forType(int $type): self
    {
        $model = static::findOne([
            'service_type' => $type,
        ]);

        if ($model === null) {
            throw new \RuntimeException(
                "Billing settings missing for type {$type}."
            );
        }

        return $model;
    }

    public static function baseAmountHint(int $type): string
    {
        return sprintf(
            'The price of one renewal cycle: the Renewal Cycle set below, or %d months if that is empty. Used for the first renewal and after a change of cycle%s. Later renewals continue from the last billed price, so use Next Renewal Price to change it.',
            self::forType($type)->renewal_cycle_months,
            $type === InvoiceItem::TYPE_HOSTING ? ' (not from a multi-year renewal to yearly, which continues from the last billed price)' : ''
        );
    }

    public static function cycleLabel(int $type): string
    {
        return 'Global setting (' . self::forType($type)->renewal_cycle_months . ' months)';
    }
}
