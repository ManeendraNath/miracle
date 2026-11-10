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
            : 'Export of services: no GST charged.';
    }

    /** The invoice this one's balance was carried into (the next link only; see InvoicePaymentService::openInvoiceCarrying()). */
    public function carriedTo(): ?self
    {
        $item = InvoiceItem::findOne(['source_invoice_id' => (int) $this->id, 'item_type' => InvoiceItem::TYPE_ARREARS]);

        return $item !== null ? self::findOne((int) $item->invoice_id) : null;
    }

    /**
     * For a renewal invoice (it bills a billing event): days since its due date, the service's expiry date
     * (0 = expires today, negative = not yet). Null for any other invoice.
     */
    public function renewalDaysExpired(): ?int
    {
        if ($this->due_date === null || !BillingEvent::find()->where(['invoice_id' => $this->id])->exists()) {
            return null;
        }

        return (int) (new \DateTimeImmutable((string) $this->due_date))->diff(new \DateTimeImmutable('today'))->format('%r%a');
    }

    /**
     * Invoices the customer may see: not one still waiting for a staff check (InvoiceReviewService).
     *
     * @return \yii\db\ActiveQuery<static>
     */
    public static function forCustomer(): \yii\db\ActiveQuery
    {
        return self::find()->andWhere(['<>', self::tableName() . '.status', self::STATUS_DRAFT])
            // A held invoice cancelled before it was sent keeps its TMP- number: it was never issued.
            ->andWhere(['not like', self::tableName() . '.invoice_number', 'TMP-%', false]);
    }

    /** How staff text names the invoice: one still held for review only has a temporary TMP- number. */
    public function reference(): string
    {
        if (!str_starts_with((string) $this->invoice_number, 'TMP-')) {
            return (string) $this->invoice_number;
        }

        return '#' . $this->id . ($this->status === self::STATUS_DRAFT ? ' (waiting for review)' : ' (cancelled before sending)');
    }

    /** How a customer message names it: one never sent has no number they could look up. */
    public function customerReference(): string
    {
        return str_starts_with((string) $this->invoice_number, 'TMP-') ? 'your upcoming renewal invoice' : 'invoice ' . $this->invoice_number;
    }

    /**
     * The status to show people: a written-off invoice is stored as Refunded, but saying so when no money
     * went back would make a customer look for a refund that never existed.
     */
    public function statusLabel(): string
    {
        return self::labelOf($this);
    }

    /** For an invoice reached through a relation, which gives the base model. */
    public static function labelOf(base\Invoice $invoice): string
    {
        if ($invoice->status === self::STATUS_REFUNDED && $invoice->written_off_at !== null) {
            return 'Closed (balance written off)';
        }

        return self::getStatuses()[$invoice->status] ?? (string) $invoice->status;
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
            self::STATUS_DRAFT => 'Waiting for review',
        ];
    }
}
