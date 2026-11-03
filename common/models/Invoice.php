<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

class Invoice extends base\Invoice
{
    public function getItems(): ActiveQuery
    {
        return $this->hasMany(InvoiceItem::class, ['invoice_id' => 'id'])->orderBy(['id' => SORT_ASC]);
    }

    public function getTransactions(): ActiveQuery
    {
        return $this->hasMany(Transaction::class, ['invoice_id' => 'id']);
    }

    public function getBillingEvents(): ActiveQuery
    {
        return $this->hasMany(BillingEvent::class, ['invoice_id' => 'id']);
    }

    /** Client outside India (older invoices have no country: India). */
    public function isExport(): bool
    {
        $country = trim((string) $this->client_country);

        return $country !== '' && $country !== CustomerBillingProfile::COUNTRY_INDIA;
    }

    /** "City, State, PIN" for India; "City, Postal code, Country" for clients abroad. */
    public function getClientCityLine(): string
    {
        $parts = $this->isExport()
            ? [$this->client_city, $this->client_postal_code, $this->client_country]
            : [$this->client_city, $this->client_state, $this->client_postal_code];

        return implode(', ', array_filter($parts, static fn ($part): bool => trim((string) $part) !== ''));
    }

    /**
     * Tax lines under the client address on a tax invoice: client GSTIN (or PAN) and place of supply.
     *
     * @return string[]
     */
    public function getClientTaxLines(): array
    {
        $lines = [];
        if (trim((string) $this->client_gstin) !== '') {
            $lines[] = 'GSTIN: ' . $this->client_gstin;
        } elseif (trim((string) $this->client_pan) !== '') {
            $lines[] = 'PAN: ' . $this->client_pan;
        }
        if (trim((string) $this->place_of_supply) !== '') {
            $code = trim((string) $this->client_state_code);
            $lines[] = 'Place of supply: ' . $this->place_of_supply . ($code !== '' ? " ({$code})" : '');
        }

        return $lines;
    }

    /** Our GSTIN for the invoice header; empty until BILLING_GSTIN is set in .env. */
    public static function supplierGstin(): string
    {
        return trim((string) (\Yii::$app->params['billing']['supplierGstin'] ?? ''));
    }

    /** Line every export invoice must carry; null for clients in India. */
    public function getExportNote(): ?string
    {
        if (!$this->isExport()) {
            return null;
        }
        $arn = trim((string) (\Yii::$app->params['billing']['lutArn'] ?? ''));

        return $arn !== ''
            ? "Export of services: supply meant for export under LUT without payment of IGST (LUT ARN {$arn})."
            : 'Export of services: zero-rated supply, no GST charged.';
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_UNPAID => 'Unpaid',
            self::STATUS_PARTIALLY_PAID => 'Partially Paid',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CARRIED_FORWARD => 'Carried Forward',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded',
        ];
    }
}
