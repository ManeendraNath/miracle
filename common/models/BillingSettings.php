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
}
