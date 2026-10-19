<?php

declare(strict_types=1);

namespace common\models;

class BillingRun extends base\BillingRun
{

    public static function getStatuses(): array
    {
        return parent::optsStatus();
    }
}