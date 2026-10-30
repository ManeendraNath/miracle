<?php

declare(strict_types=1);

namespace common\services\billing;

use DateTimeImmutable;

final class RenewalPeriodCalculator
{
    public function calculate(
        string $expiryDate,
        int $months
    ): array {
        if ($months < 1) {
            throw new \InvalidArgumentException(
                'Renewal period must be positive.'
            );
        }

        $expiry = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $expiryDate
        );

        $errors = DateTimeImmutable::getLastErrors();

        if ($expiry === false) {
            throw new \InvalidArgumentException(
                'Invalid expiry date '
                            . $expiryDate
                            . '.'
            );
        }

        if (
                is_array($errors) && (
                $errors['warning_count'] > 0 || $errors['error_count'] > 0
                )
        ) {
            throw new \InvalidArgumentException(
                'Invalid expiry date '
                            . $expiryDate
                            . '.'
            );
        }

        $start = $expiry->modify('+1 day');

        // Same day-of-month N months later, clamped to the target month's
        // length ("+N months" would overflow 30 Jan -> 2 Mar). A month-end
        // expiry stays at month end (28 Feb -> 31 Mar).
        $target = $expiry->modify('first day of +' . $months . ' months');
        $day = $expiry->format('j') === $expiry->format('t')
                ? (int) $target->format('t')
                : min((int) $expiry->format('j'), (int) $target->format('t'));
        $end = $target->setDate(
            (int) $target->format('Y'),
            (int) $target->format('n'),
            $day
        );

        return [
            'start' =>
            $start->format('Y-m-d'),
            'end' =>
            $end->format('Y-m-d'),
        ];
    }
}
