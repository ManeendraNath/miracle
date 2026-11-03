<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use Yii;

final class TaxCalculator
{
    public function getRates(CustomerBillingProfile $profile): array
    {
        // Export of services (client outside India) is zero-rated.
        if (!(bool) $profile->is_gst_applicable || $profile->isForeign()) {
            return [
                'cgst' => 0.0,
                'sgst' => 0.0,
                'igst' => 0.0,
            ];
        }
        $billingConfig = Yii::$app->params['billing'] ?? [];

        $gstConfig = $billingConfig['gst'] ?? [];

        $supplierStateCode = trim((string) ($billingConfig['supplierStateCode'] ?? ''));

        $customerStateCode = trim((string) $profile->state_code);

        if ($supplierStateCode === '') {
            throw new \RuntimeException('Supplier state code is not configured.');
        }

        if ($customerStateCode === '') {
            throw new \DomainException(
                'Customer billing state code is required ' . 'before an invoice can be generated.',
            );
        }

        $cgst = (float) ($gstConfig['cgst'] ?? 0);

        $sgst = (float) ($gstConfig['sgst'] ?? 0);

        $igst = (float) ($gstConfig['igst'] ?? 0);

        if ($cgst < 0 || $sgst < 0 || $igst < 0) {
            throw new \RuntimeException('GST percentages cannot be negative.');
        }

        if ($customerStateCode === $supplierStateCode) {
            return [
                'cgst' => $cgst,
                'sgst' => $sgst,
                'igst' => 0.0,
            ];
        }

        return [
            'cgst' => 0.0,
            'sgst' => 0.0,
            'igst' => $igst,
        ];
    }

    /**
     * Tax split of a saved invoice. The invoice PDF, the admin and customer invoice pages and the
     * GST report all read it from here so they can't disagree.
     *
     * @return array{taxable: float, cgstAmount: float, sgstAmount: float, igstAmount: float, totalTax: float, total: float}
     */
    public function forInvoice(Invoice $invoice): array
    {
        // Carried-forward balance lines were taxed on their own invoice; only current charges are taxed
        // here (same as AutoInvoiceService::calculateInvoiceTotals, which sets total_payable).
        $arrears = (float) InvoiceItem::find()
            ->where(['invoice_id' => $invoice->id, 'item_type' => InvoiceItem::TYPE_ARREARS])
            ->sum('total_price');
        $taxable = (float) max(
            0,
            round((float) $invoice->subtotal_amount - $arrears - (float) $invoice->discount_amount, 2),
        );

        return ['taxable' => $taxable] +
            $this->calculate(
                $taxable,
                (float) $invoice->cgst_percent,
                (float) $invoice->sgst_percent,
                (float) $invoice->igst_percent,
            );
    }

    public function calculate(float $taxableAmount, float $cgstPercent, float $sgstPercent, float $igstPercent): array
    {
        $taxableAmount = max(0, round($taxableAmount, 2));

        $cgstAmount = round($taxableAmount * ($cgstPercent / 100), 2);

        $sgstAmount = round($taxableAmount * ($sgstPercent / 100), 2);

        $igstAmount = round($taxableAmount * ($igstPercent / 100), 2);

        return [
            'cgstAmount' => $cgstAmount,
            'sgstAmount' => $sgstAmount,
            'igstAmount' => $igstAmount,
            'totalTax' => round($cgstAmount + $sgstAmount + $igstAmount, 2),
            'total' => round($taxableAmount + $cgstAmount + $sgstAmount + $igstAmount, 2),
        ];
    }
}
