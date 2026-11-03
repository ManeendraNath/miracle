<?php

declare(strict_types=1);

namespace common\services\billing;

use DateTimeImmutable;

final class RenewalPeriodCalculator
{
    public function calculate(string $expiryDate, int $months): array
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

        // Same day-of-month N months later, clamped to the target month's
        // length ("+N months" would overflow 30 Jan -> 2 Mar). For monthly-style
        // cycles a month-end expiry stays at month end (28 Feb -> 31 Mar).
        // Whole years keep the calendar date like a registrar (28 Feb -> 28 Feb,
        // 29 Feb -> 28 Feb), otherwise 28 Feb would jump to 29 Feb in leap years.
        // ponytail: without a stored anchor day, a monthly expiry clamped to a short
        // month (30 Jan -> 28 Feb) then rides month end; store the anchor day if that matters.
        $target = $expiry->modify('first day of +' . $months . ' months');
        $day =
            $months % 12 !== 0 && $expiry->format('j') === $expiry->format('t')
                ? (int) $target->format('t')
                : min((int) $expiry->format('j'), (int) $target->format('t'));
        $end = $target->setDate((int) $target->format('Y'), (int) $target->format('n'), $day);

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
    }
}
