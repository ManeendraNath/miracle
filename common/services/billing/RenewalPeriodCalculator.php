<?php

declare(strict_types=1);

namespace common\services\billing;

use DateTimeImmutable;

final class RenewalPeriodCalculator
{

    public function calculate(
            string $expiryDate,
            int $months
    ): array
    {
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

        $end = $start
                ->modify(
                        '+' . $months . ' months'
                )
                ->modify('-1 day');

        return [
            'start' =>
            $start->format('Y-m-d'),
            'end' =>
            $end->format('Y-m-d'),
        ];
    }
}
