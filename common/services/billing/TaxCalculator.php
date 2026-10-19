<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerBillingProfile;
use Yii;

final class TaxCalculator
{

    public function getRates(
            CustomerBillingProfile $profile
    ): array
    {
        $billingConfig = Yii::$app->params['billing'] ?? [];

        $gstConfig = $billingConfig['gst'] ?? [];

        $supplierStateCode = trim(
                (string) (
                $billingConfig['supplierStateCode'] ?? ''
                )
        );

        $customerStateCode = trim(
                (string) $profile->state_code
        );

        if ($supplierStateCode === '') {
            throw new \RuntimeException(
                            'Supplier state code is not configured.'
                    );
        }

        if ($customerStateCode === '') {
            throw new \DomainException(
                            'Customer billing state code is required '
                            . 'before an invoice can be generated.'
                    );
        }

        $cgst = (float) (
                $gstConfig['cgst'] ?? 0
        );

        $sgst = (float) (
                $gstConfig['sgst'] ?? 0
        );

        $igst = (float) (
                $gstConfig['igst'] ?? 0
        );

        if (
                $cgst < 0 || $sgst < 0 || $igst < 0
        ) {
            throw new \RuntimeException(
                            'GST percentages cannot be negative.'
                    );
        }

        if (
                $customerStateCode === $supplierStateCode
        ) {
            return [
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => 0.00,
            ];
        }

        return [
            'cgst' => 0.00,
            'sgst' => 0.00,
            'igst' => $igst,
        ];
    }

    public function calculate(
            float $taxableAmount,
            float $cgstPercent,
            float $sgstPercent,
            float $igstPercent
    ): array
    {
        $taxableAmount = max(
                0,
                round($taxableAmount, 2)
        );

        $cgstAmount = round(
                $taxableAmount * ($cgstPercent / 100),
                2
        );

        $sgstAmount = round(
                $taxableAmount * ($sgstPercent / 100),
                2
        );

        $igstAmount = round(
                $taxableAmount * ($igstPercent / 100),
                2
        );

        return [
            'cgstAmount' =>
            $cgstAmount,
            'sgstAmount' =>
            $sgstAmount,
            'igstAmount' =>
            $igstAmount,
            'totalTax' =>
            round(
                    $cgstAmount + $sgstAmount + $igstAmount,
                    2
            ),
            'total' =>
            round(
                    $taxableAmount + $cgstAmount + $sgstAmount + $igstAmount,
                    2
            ),
        ];
    }
}
