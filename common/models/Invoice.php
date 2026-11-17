<?php

declare(strict_types=1);

namespace common\models;

use yii\db\ActiveQuery;

/**
 * @property-read InvoiceItem[] $items
 */
class Invoice extends base\Invoice
{
    public const CURRENCY_INR = 'INR';
    public const CURRENCY_USD = 'USD';

    /** The staff invoice form's "Prices in" choice: USD prices the lines in dollars (clients abroad only). */
    /** @var string|null untyped: a malformed post must fail validation, not throw */
    public $priceCurrency = null;

    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            ['priceCurrency', 'in', 'range' => [self::CURRENCY_INR, self::CURRENCY_USD]],
        ]);
    }

    /** Set by the app only, never from a form: still validated, but not mass-assigned ("!"). */
    #[\Override]
    public function scenarios(): array
    {
        $internal = ['usd_rate_locked', 'usd_rate_until', 'usd_fee_percent_locked', 'usd_fee_fixed_locked', 'written_off_at', 'usd_total', 'usd_rate_date', 'usd_rate_source'];

        return array_map(
            static fn(array $attributes): array => array_map(static fn(string $name): string => in_array($name, $internal, true) ? '!' . $name : $name, $attributes),
            parent::scenarios(),
        );
    }

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

    /**
     * Priced in US dollars: the client pays exactly usd_total by PayPal. The books stay in rupees, each line at the
     * reference rate of the invoice date (usd_rate_locked), so every rupee figure in the app still applies.
     */
    public function isUsdPriced(): bool
    {
        return $this->usd_total !== null && (float) $this->usd_rate_locked > 0;
    }

    /** $inr of this USD-priced invoice in dollars, at its rate; exact for any amount derived from its dollar prices. */
    public function usdFor(float $inr): float
    {
        return round($inr / (float) $this->usd_rate_locked, 2);
    }

    /** $usd of this USD-priced invoice in rupees, at its rate. */
    public function inrFor(float $usd): float
    {
        return round($usd * (float) $this->usd_rate_locked, 2);
    }

    /**
     * An amount of this invoice (stored in rupees) as shown to people: in dollars when priced in dollars, else rupees.
     * $plain for PDFs and text emails ("USD 1,999.98" / "INR 1,76,245.04"), else "US$1,999.98" / "₹1,76,245.04".
     */
    public function money(int|float|string|null $inr, bool $plain = false): string
    {
        $inr = (float) ($inr ?? 0);
        if ($this->isUsdPriced()) {
            return ($plain ? 'USD ' : 'US$') . number_format($this->usdFor($inr), 2);
        }

        return $plain ? 'INR ' . number_format($inr, 2) : \Yii::$app->formatter->asCurrency($inr, 'INR');
    }

    /** For an invoice priced in dollars: its value in rupees and the rate behind it (GST valuation); else null. */
    public function usdValueNote(bool $plain = false): ?string
    {
        if (!$this->isUsdPriced()) {
            return null;
        }
        $rupees = static fn(float $amount): string => $plain ? 'INR ' . number_format($amount, 2) : \Yii::$app->formatter->asCurrency($amount, 'INR');

        return 'Value in Indian rupees: ' . $rupees((float) $this->total_payable) . ' at ' . $rupees((float) $this->usd_rate_locked) . ' per US$1 ('
            . $this->usd_rate_source . ' of ' . date('d M Y', (int) strtotime((string) $this->usd_rate_date)) . ').';
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

    /**
     * Our address and contact lines for the invoice header, from Site Settings (as the website shows them).
     * @return string[]
     */
    public static function supplierLines(): array
    {
        $setting = static fn(string $name): string => trim((string) \common\components\CustomYii::getSetting($name), ' ,');
        $address = array_filter([$setting('address_line_1'), $setting('address_line_2'), $setting('address_line_3')]);

        return array_values(array_filter([
            implode(', ', $address),
            implode(' | ', array_filter([$setting('email_1'), $setting('mobile_1') !== '' ? \common\components\CustomYii::formatPhone($setting('mobile_1')) : ''])),
        ]));
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

    /** Plain names on forms and errors instead of the column names. */
    public function attributeLabels(): array
    {
        return \yii\helpers\ArrayHelper::merge(parent::attributeLabels(), [
            'user_id' => 'Client',
            'coupon_id' => 'Coupon',
            'cgst_percent' => 'CGST (%)',
            'sgst_percent' => 'SGST (%)',
            'igst_percent' => 'IGST (%)',
            'billing_run_id' => 'Billing Run',
        ]);
    }

    /** Renewal and carried-forward lines are made by the system: an invoice with any can't be edited by hand. */
    public function hasSystemItems(): bool
    {
        return self::idsWithSystemItems([(int) $this->id]) !== [];
    }

    /**
     * hasSystemItems() for a whole page of invoices in one query.
     *
     * @param int[] $ids
     * @return int[]
     */
    public static function idsWithSystemItems(array $ids): array
    {
        return $ids === [] ? [] : array_map('intval', InvoiceItem::find()
            ->select('invoice_id')
            ->distinct()
            ->where(['invoice_id' => $ids])
            ->andWhere(['or', ['not', ['billing_event_id' => null]], ['not', ['source_invoice_id' => null]]])
            ->column());
    }
}
