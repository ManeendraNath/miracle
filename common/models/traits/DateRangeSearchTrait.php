<?php

declare(strict_types=1);

namespace common\models\traits;

use DateTimeImmutable;
use DateTimeZone;
use yii\db\ActiveQuery;

trait DateRangeSearchTrait
{
    /**
     * Apply a date range to a UNIX timestamp database column.
     *
     * Expected format:
     * 2026-10-01 - 2026-10-13
     */
    protected function applyTimestampDateRange(ActiveQuery $query, string $column, ?string $range, ?string $timezone = null): void {
        if ($range === null || trim($range) === '') {
            return;
        }

        $dates = array_map('trim', explode(' - ', trim($range)));

        if (count($dates) !== 2) {
            return;
        }

        $timezone = new DateTimeZone($timezone ?: date_default_timezone_get());
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $dates[0], $timezone);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $dates[1], $timezone);

        if ($start === false || $end === false) {
            return;
        }

        /*
         * Invalid/reversed range.
         */
        if ($start > $end) {
            return;
        }

        $startTimestamp = $start->getTimestamp();

        /*
         * Exclusive upper boundary:
         *
         * selected:
         * 2026-10-01 - 2026-10-13
         *
         * becomes:
         *
         * >= 2026-10-01 00:00:00
         * <  2026-10-14 00:00:00
         */
        $endTimestamp = $end->modify('+1 day')->getTimestamp();
        $query->andWhere(['>=', $column, $startTimestamp])
            ->andWhere(['<', $column, $endTimestamp]);
    }

    /**
     * Apply a date range to a SQL DATE/DATETIME column.
     *
     * This is kept separate from UNIX timestamp handling so
     * applications can reuse this trait with different schemas.
     */
    protected function applyDateRange(ActiveQuery $query, string $column, ?string $range): void {
        if ($range === null || trim($range) === '') {
            return;
        }

        $dates = array_map('trim', explode(' - ', trim($range)));

        if (count($dates) !== 2) {
            return;
        }

        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $dates[0]);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $dates[1]);

        if ($start === false || $end === false || $start > $end) {
            return;
        }

        $query->andWhere([
            'between',
            $column,
            $start->format('Y-m-d 00:00:00'),
            $end->format('Y-m-d 23:59:59'),
        ]);
    }
}