<?php

declare(strict_types=1);

namespace common\services\billing\dto;

final readonly class RenewalPriceResult
{
    public function __construct(
        public float $previousAmount,
        public float $increasePercent,
        public float $amount
    ) {
    }
}