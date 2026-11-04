<?php

declare(strict_types=1);

namespace common\services\billing;

use DateTimeImmutable;

final class RenewalPeriodCalculator
{
    /**
     * Whole months a billed period covers, rounded: 01 May -> 30 May (anchor 30) is 1, 01 Mar 2027 ->
     * 28 Feb 2028 (yearly from a 28 Feb expiry) is 12. Periods are inclusive of both dates.
     */
    public static function months(string $periodStart, string $periodEnd): int
    {
        $diff = (new DateTimeImmutable($periodStart))->diff((new DateTimeImmutable($periodEnd))->modify('+1 day'));

        return $diff->y * 12 + $diff->m + ($diff->d >= 15 ? 1 : 0);
    }

    /**
     * @param int|null $anchorDay day of month the service renews on (31 = month end); null = derive from the expiry
     */
    public function calculate(string $expiryDate, int $months, ?int $anchorDay = null): array
    {
        if ($months < 1) {
            throw new \InvalidArgumentException('Renewal period must be positive.');
        }

        $expiry = DateTimeImmutable::createFromFormat('!Y-m-d', $expiryDate);

        $errors = DateTimeImmutable::getLastErrors();

        if ($expiry === false) {
            throw new \InvalidArgumentException('Invalid expiry date ' . $expiryDate . '.');
        }

        if (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            throw new \InvalidArgumentException('Invalid expiry date ' . $expiryDate . '.');
        }

        $start = $expiry->modify('+1 day');

        // Same day-of-month N months later, clamped to the target month's length
        // ("+N months" would overflow 30 Jan -> 2 Mar).
        // - Whole years keep the calendar date like a registrar (28 Feb -> 28 Feb, 29 Feb -> 28 Feb).
        // - Other cycles return to the service's anchor day after a short month
        //   (anchor 30: 30 Jan -> 28 Feb -> 30 Mar). Without an anchor, a month-end
        //   expiry stays at month end (28 Feb -> 31 Mar).
        $target = $expiry->modify('first day of +' . $months . ' months');
        $daysInTarget = (int) $target->format('t');
        if ($months % 12 === 0) {
            $day = min((int) $expiry->format('j'), $daysInTarget);
        } elseif ($anchorDay !== null) {
            $day = min(max(1, $anchorDay), $daysInTarget);
        } else {
            $day = $expiry->format('j') === $expiry->format('t') ? $daysInTarget : min((int) $expiry->format('j'), $daysInTarget);
        }
        $end = $target->setDate((int) $target->format('Y'), (int) $target->format('n'), $day);

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
    }

    /** Anchor day for an expiry date staff entered: its day of month, or 31 when it is a month end. */
    public static function anchorDayFor(string $date): ?int
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($parsed === false) {
            return null;
        }

        return $parsed->format('j') === $parsed->format('t') ? 31 : (int) $parsed->format('j');
    }
}
