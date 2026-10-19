<?php

declare(strict_types=1);

namespace common\services\billing\dto;

/**
 * Immutable renewable-asset snapshot collected before
 * a BillingEvent is created.
 *
 * This DTO intentionally contains no Invoice or BillingRun state.
 */
final readonly class BillableRenewal
{
    public function __construct(
        public int $userId,
        public int $itemType,
        public int $itemId,
        public string $description,
        public string $currentExpiryDate,
        public float $baseAmount,
        public ?int $cycleMonths = null,
        public ?float $increasePercent = null
    ) {
    }
}