<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\Transaction;
use common\models\User;
use common\services\notification\NotificationService;
use common\services\payment\ExchangeRateService;
use common\services\payment\RazorpayService;
use common\models\forms\ManualInvoiceItemForm;
use Yii;

final class ManualInvoiceService
{
    /** Something staff should be told about the last update. */
    public ?string $notice = null;

    public function __construct(
        private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
        private readonly InvoiceNumberService $numberService = new InvoiceNumberService(),
        private readonly InvoicePaymentService $paymentService = new InvoicePaymentService(),
        private readonly CouponService $couponService = new CouponService(),
        private readonly ExchangeRateService $exchangeRates = new ExchangeRateService(),
    ) {
    }

    public function create(Invoice $invoice, array $items): Invoice
    {
        if (!in_array($invoice->priceCurrency, [null, Invoice::CURRENCY_INR, Invoice::CURRENCY_USD], true)) {
            throw new \DomainException('Choose rupees or US dollars for the prices.');
        }
        if (!$items) {
            throw new \DomainException('At least one invoice item is required.');
        }

        if (empty($invoice->user_id)) {
            throw new \DomainException('A client must be selected.');
        }

        if (empty($invoice->due_date)) {
            throw new \DomainException('Invoice due date is required.');
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $client = User::findOne((int) $invoice->user_id);

            if ($client === null || (int) $client->status === User::getStatusDeleted()) {
                throw new \DomainException('Selected client does not exist.');
            }

            $profile = CustomerBillingProfile::findOne([
                'user_id' => $client->id,
            ]);

            if ($profile === null) {
                throw new \DomainException(
                    'Please complete the client billing ' . 'profile before creating an invoice.',
                );
            }

            $this->applyClientSnapshot($invoice, $profile);

            $this->applyTax($invoice, $profile);

            $rate = $invoice->priceCurrency === Invoice::CURRENCY_USD ? $this->usdRate($invoice, $profile, date('Y-m-d')) : null;
            $lines = $this->priceLines($items, $rate);
            $subtotal = $lines['subtotal'];

            if ($subtotal <= 0) {
                throw new \DomainException('Invoice subtotal must be greater than zero.');
            }

            $couponCode = strtoupper(trim((string) $invoice->coupon_code));

            if ($couponCode !== '') {
                $discount = $this->couponService->calculate(
                    $couponCode,
                    (int) $invoice->user_id,
                    $subtotal,
                    null,
                    false,
                    $this->totalsByType($items),
                );

                $invoice->coupon_code = $couponCode;
            } else {
                $discount = max(0, round((float) $invoice->discount_amount, 2));

                $invoice->coupon_code = null;
            }

            if ($discount > $subtotal) {
                throw new \DomainException('Invoice discount cannot exceed subtotal.');
            }
            if ($rate !== null && ($couponCode !== '' || $discount > 0)) {
                throw new \DomainException('An invoice priced in US dollars takes no coupon or discount: enter the final dollar prices.');
            }

            $taxable = round($subtotal - $discount, 2);

            $tax = $this->taxCalculator->calculate(
                $taxable,
                (float) $invoice->cgst_percent,
                (float) $invoice->sgst_percent,
                (float) $invoice->igst_percent,
            );

            if ($tax['total'] <= 0) {
                throw new \DomainException('Invoice total must be greater than zero.');
            }
            $this->applyUsdTotal($invoice, $rate, $lines['usdTotal'], $tax['total'] === $subtotal);
            $now = time();
            $invoice->billing_run_id = null;
            $invoice->subtotal_amount = $subtotal;
            $invoice->discount_amount = $discount;
            $invoice->total_payable = $tax['total'];
            $invoice->invoice_number = $this->numberService->createTemporaryNumber();
            $invoice->status = Invoice::STATUS_UNPAID;
            $invoice->created_at = $now;
            $invoice->updated_at = $now;
            if (!$invoice->save()) {
                throw new \RuntimeException(
                    'Invoice could not be created: ' .
                        json_encode($invoice->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );
            }
            foreach ($lines['lines'] as $line) {
                $this->createItem($invoice, $line);
            }
            $this->numberService->assign($invoice);
            if ($invoice->coupon_code !== null) {
                $this->couponService->record($invoice);
            }
            $transaction->commit();
            // Committed: a failed notification must not look like a failed invoice (staff would create another).
            try {
                (new NotificationService())->create(
                    (int) $invoice->user_id,
                    'invoice',
                    'New Invoice Generated',
                    'Invoice ' . $invoice->invoice_number . ' has been generated.',
                    '/invoice/view?number=' . rawurlencode($invoice->invoice_number),
                );
            } catch (\Throwable $e) {
                Yii::error(['message' => 'New invoice notification failed.', 'invoiceId' => (int) $invoice->id, 'error' => $e->getMessage()], __METHOD__);
            }
            return $invoice;
        } catch (\Throwable $e) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $e;
        }
    }

    public function update(Invoice $invoice, array $items): Invoice
    {
        if (!in_array($invoice->priceCurrency, [null, Invoice::CURRENCY_INR, Invoice::CURRENCY_USD], true)) {
            throw new \DomainException('Choose rupees or US dollars for the prices.');
        }
        if (!$items) {
            throw new \DomainException('At least one invoice item is required.');
        }

        if (!in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_DRAFT], true)) {
            throw new \DomainException('Only unpaid invoices can be modified.');
        }

        if (empty($invoice->due_date)) {
            throw new \DomainException('Invoice due date is required.');
        }

        $this->calculateSubtotal($items);

        $couponCode = strtoupper(trim((string) $invoice->coupon_code));

        $dbTransaction = Yii::$app->db->beginTransaction();

        try {
            RazorpayService::lockUnpaidOrders((int) $invoice->id);
            $invoiceId = Yii::$app->db
                ->createCommand(
                    'SELECT id
                 FROM {{%invoice}}
                 WHERE id = :id
                 FOR UPDATE',
                )
                ->bindValue(':id', (int) $invoice->id)
                ->queryScalar();

            $lockedInvoice = $invoiceId !== false ? Invoice::findOne((int) $invoiceId) : null;

            if ($lockedInvoice === null) {
                throw new \RuntimeException('Invoice no longer exists.');
            }

            if (!in_array($lockedInvoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_DRAFT], true)) {
                throw new \DomainException(
                    'Invoice status changed while editing. ' . 'The invoice can no longer be modified.',
                );
            }

            $capturedAmount = $this->paymentService->getCapturedAmount((int) $lockedInvoice->id);

            if ($capturedAmount > 0) {
                throw new \DomainException('An invoice with captured payments ' . 'cannot be modified.');
            }

            // A payment proof was submitted for the current total: approve or reject it first. An unpaid
            // online order is retired instead (as a coupon change does), so an abandoned checkout can't lock the invoice.
            if ($this->paymentService->hasPendingProof((int) $lockedInvoice->id)) {
                throw new \DomainException(
                    'This invoice has a payment proof awaiting review. Approve or reject it before editing.',
                );
            }
            RazorpayService::supersedeUnpaidOrders((int) $lockedInvoice->id);

            $hasAutomaticItems = InvoiceItem::find()
                ->where([
                    'invoice_id' => $lockedInvoice->id,
                ])
                ->andWhere([
                    'not',
                    [
                        'billing_event_id' => null,
                    ],
                ])
                ->exists();

            if ($hasAutomaticItems) {
                throw new \DomainException('Automatic renewal invoices ' . 'cannot be modified manually.');
            }

            $hasArrears = InvoiceItem::find()
                ->where([
                    'invoice_id' => $lockedInvoice->id,
                    'item_type' => InvoiceItem::TYPE_ARREARS,
                ])
                ->exists();

            if ($hasArrears) {
                throw new \DomainException(
                    'Invoices containing carried-forward ' . 'balances cannot be modified manually.',
                );
            }

            $invalidManualItems = InvoiceItem::find()
                ->where([
                    'invoice_id' => $lockedInvoice->id,
                ])
                ->andWhere(['not in', 'item_type', array_keys(InvoiceItem::getServiceTypes())])
                ->exists();

            if ($invalidManualItems) {
                throw new \DomainException('Only fully manual invoices can be ' . 'modified using this editor.');
            }

            // Prices in dollars or rupees: as chosen on the form, else as the invoice already is.
            $currency = $invoice->priceCurrency ?? ($lockedInvoice->isUsdPriced() ? Invoice::CURRENCY_USD : Invoice::CURRENCY_INR);
            $rate = null;
            if ($currency === Invoice::CURRENCY_USD) {
                $profile = CustomerBillingProfile::findOne(['user_id' => (int) $lockedInvoice->user_id]);
                // Already in dollars: its rate stays, being the rate of its (unchanged) invoice date.
                $rate = $lockedInvoice->isUsdPriced() && $profile !== null && $profile->isForeign()
                    ? (float) $lockedInvoice->usd_rate_locked
                    : $this->usdRate($lockedInvoice, $profile, date('Y-m-d', (int) $lockedInvoice->created_at));
                // Client and tax as they are now (abroad: no GST), not as when the invoice was first made in rupees.
                $this->applyClientSnapshot($lockedInvoice, $profile);
                $this->applyTax($lockedInvoice, $profile);
            }
            $lines = $this->priceLines($items, $rate);
            $subtotal = $lines['subtotal'];
            if ($subtotal <= 0) {
                throw new \DomainException('Invoice subtotal must be greater than zero.');
            }

            $storedCode = strtoupper(trim((string) $lockedInvoice->coupon_code));
            $newTotals = $this->totalsByType($items);
            $storedTotals = [];
            foreach (InvoiceItem::find()->where(['invoice_id' => $lockedInvoice->id])->all() as $line) {
                $storedTotals[$line->item_type] = round(($storedTotals[$line->item_type] ?? 0) + (float) $line->total_price, 2);
            }
            ksort($newTotals);
            ksort($storedTotals);
            $keepingCoupon = $couponCode !== '' && $couponCode === $storedCode && $newTotals == $storedTotals;
            if ($keepingCoupon) {
                // Same coupon, same lines: the discount the customer was given stands, whatever became of the coupon since.
                $discount = round((float) $lockedInvoice->discount_amount, 2);
            } elseif ($couponCode !== '') {
                $discount = $this->couponService->calculate(
                    $couponCode,
                    (int) $lockedInvoice->user_id,
                    $subtotal,
                    (int) $lockedInvoice->id,
                    // Keeping the coupon it already has: an edit must not fail
                    // because that coupon has since expired or been disabled.
                    $couponCode === $storedCode,
                    $newTotals,
                );
            } else {
                $discount = max(0, round((float) $invoice->discount_amount, 2));
                // The coupon was removed: its discount goes with it, unless staff typed a new figure.
                if ((string) $lockedInvoice->coupon_code !== '' && abs($discount - (float) $lockedInvoice->discount_amount) < 0.005) {
                    $this->notice = 'The coupon was removed, so its discount of ₹' . number_format($discount, 2) . ' was removed too.';
                    $discount = 0.0;
                }
            }

            if ($discount > $subtotal) {
                throw new \DomainException('Invoice discount cannot exceed subtotal.');
            }

            $existingItems = InvoiceItem::find()
                ->where([
                    'invoice_id' => $lockedInvoice->id,
                    'item_type' => array_keys(InvoiceItem::getServiceTypes()),
                ])
                ->orderBy([
                    'id' => SORT_ASC,
                ])
                ->all();

            foreach ($existingItems as $existingItem) {
                if ($existingItem->delete() === false) {
                    throw new \RuntimeException('Existing manual invoice item ' . 'could not be removed.');
                }
            }

            foreach ($lines['lines'] as $line) {
                $this->createItem($lockedInvoice, $line);
            }

            $taxable = round($subtotal - $discount, 2);

            $tax = $this->taxCalculator->calculate(
                $taxable,
                (float) $lockedInvoice->cgst_percent,
                (float) $lockedInvoice->sgst_percent,
                (float) $lockedInvoice->igst_percent,
            );

            if ($tax['total'] <= 0) {
                throw new \DomainException('Invoice total must be greater than zero.');
            }

            if ($rate !== null && ($couponCode !== '' || $discount > 0)) {
                throw new \DomainException('An invoice priced in US dollars takes no coupon or discount: enter the final dollar prices.');
            }
            $this->applyUsdTotal($lockedInvoice, $rate, $lines['usdTotal'], $tax['total'] === $subtotal);
            $lockedInvoice->discount_amount = $discount;

            $lockedInvoice->coupon_code = $couponCode !== '' ? $couponCode : null;
            if ($couponCode === '') {
                $lockedInvoice->coupon_id = null;
            }

            $lockedInvoice->due_date = $invoice->due_date;

            $lockedInvoice->subtotal_amount = $subtotal;

            $lockedInvoice->total_payable = $tax['total'];

            $lockedInvoice->updated_at = time();

            if (!$lockedInvoice->save()) {
                throw new \RuntimeException(
                    'Invoice could not be updated: ' .
                        json_encode($lockedInvoice->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );
            }

            if ($couponCode !== '') {
                $this->couponService->record($lockedInvoice, $keepingCoupon);
            } else {
                $this->couponService->removeForInvoice((int) $lockedInvoice->id);
            }

            $dbTransaction->commit();

            $invoice->refresh();

            return $invoice;
        } catch (\Throwable $e) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }

            throw $e;
        }
    }

    /**
     * @param ManualInvoiceItemForm[] $items
     * @return array<int, float> line totals summed per item_type, for coupon restrictions
     */
    private function totalsByType(array $items): array
    {
        $totals = [];
        foreach ($items as $item) {
            $totals[$item->item_type] = round(($totals[$item->item_type] ?? 0) + $item->getTotal(), 2);
        }
        return $totals;
    }

    /**
     * The reference rate of $date for an invoice priced in dollars: clients abroad only (no GST), and never a guess.
     */
    private function usdRate(Invoice $invoice, ?CustomerBillingProfile $profile, string $date): float
    {
        if ($profile === null || !$profile->isForeign()) {
            throw new \DomainException('Prices in US dollars are for clients abroad only. This client\'s billing country is India.');
        }
        $reference = $this->exchangeRates->referenceRate($date);
        if ($reference === null) {
            throw new \DomainException('The US dollar exchange rate could not be fetched, so the invoice was not saved. Try again in a few minutes.');
        }
        $invoice->usd_rate_locked = $reference['rate'];
        $invoice->usd_rate_date = $reference['date'];
        $invoice->usd_rate_source = $reference['source'];

        return $reference['rate'];
    }

    /**
     * Line prices as stored. In rupees: as entered. In dollars ($rate given): each unit price in rupees at the rate,
     * so a line is quantity x shown price in both currencies, and the rupee total converts back to exactly the
     * dollar total.
     *
     * @param ManualInvoiceItemForm[] $items
     * @return array{lines: list<array{form: ManualInvoiceItemForm, unit: float, total: float, usdUnit: ?float, usdTotal: ?float}>, subtotal: float, usdTotal: ?float}
     */
    private function priceLines(array $items, ?float $rate): array
    {
        $this->calculateSubtotal($items);
        $lines = [];
        $subtotal = 0.0;
        $usdTotal = 0.0;
        foreach ($items as $form) {
            $price = round($form->unit_price, 2);
            if ($rate === null) {
                $lines[] = ['form' => $form, 'unit' => $price, 'total' => $form->getTotal(), 'usdUnit' => null, 'usdTotal' => null];
                $subtotal += $form->getTotal();
                continue;
            }
            $unit = round($price * $rate, 2);
            if (round(round($form->quantity * $unit, 2) / $rate, 2) !== $form->getTotal()) {
                throw new \DomainException('These quantities are too large to price exactly in US dollars. Put the work in fewer units (e.g. 1 x the total).');
            }
            $lines[] = ['form' => $form, 'unit' => $unit, 'total' => round($form->quantity * $unit, 2), 'usdUnit' => $price, 'usdTotal' => $form->getTotal()];
            $subtotal += round($form->quantity * $unit, 2);
            $usdTotal += $form->getTotal();
        }
        $subtotal = round($subtotal, 2);
        if ($rate !== null && round($subtotal / $rate, 2) !== round($usdTotal, 2)) {
            // Each unit's rupee price is rounded to the paisa: with very many units those add up past a cent.
            throw new \DomainException('These quantities are too large to price exactly in US dollars. Put the work in fewer units (e.g. 1 x the total).');
        }

        return ['lines' => $lines, 'subtotal' => $subtotal, 'usdTotal' => $rate !== null ? round($usdTotal, 2) : null];
    }

    /** Dollar-priced: the dollar total, a permanent rate and no PayPal fee on top. Rupee-priced: none of it. */
    private function applyUsdTotal(Invoice $invoice, ?float $rate, ?float $usdTotal, bool $untaxed): void
    {
        if ($rate !== null && !$untaxed) {
            throw new \DomainException('An invoice priced in US dollars carries no GST: check that the client\'s billing country is outside India.');
        }
        if ($rate === null) {
            if ($invoice->usd_total !== null) {
                $invoice->setAttributes(['usd_total' => null, 'usd_rate_locked' => null, 'usd_rate_until' => null, 'usd_rate_date' => null, 'usd_rate_source' => null, 'usd_fee_percent_locked' => null, 'usd_fee_fixed_locked' => null], false);
            }

            return;
        }
        $invoice->setAttributes(['usd_total' => $usdTotal, 'usd_rate_locked' => $rate, 'usd_rate_until' => null, 'usd_fee_percent_locked' => 0, 'usd_fee_fixed_locked' => 0], false);
    }

    private function calculateSubtotal(array $items): float
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            if (!($item instanceof ManualInvoiceItemForm)) {
                throw new \InvalidArgumentException('Invalid manual invoice item.');
            }

            if (!$item->validate()) {
                throw new \DomainException(
                    'Invalid invoice item: ' .
                        json_encode($item->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                );
            }

            $subtotal += $item->getTotal();
        }

        return round($subtotal, 2);
    }

    /** @param array{form: ManualInvoiceItemForm, unit: float, total: float, usdUnit: ?float, usdTotal: ?float} $line */
    private function createItem(Invoice $invoice, array $line): void
    {
        $form = $line['form'];
        $item = new InvoiceItem();

        $item->invoice_id = (int) $invoice->id;

        $item->item_type = $form->item_type;

        $item->item_id = null;

        $item->billing_event_id = null;

        $item->source_invoice_id = null;

        $item->category = $form->category;

        $item->description = $form->description;

        $item->quantity = $form->quantity;

        $item->unit_price = $line['unit'];
        $item->total_price = $line['total'];
        $item->usd_unit_price = $line['usdUnit'];
        $item->usd_total_price = $line['usdTotal'];

        if (!$item->save()) {
            throw new \RuntimeException(
                'Invoice item could not be saved: ' .
                    json_encode($item->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            );
        }
    }

    private function applyClientSnapshot(Invoice $invoice, CustomerBillingProfile $profile): void
    {
        $invoice->client_name = $profile->legal_name;
        $invoice->client_address_line_1 = $profile->address_line_1;
        $invoice->client_address_line_2 = $profile->address_line_2;
        $invoice->client_city = $profile->city;
        // A profile saved before the foreign-state rule may still carry a state abroad: never on an invoice.
        ['state' => $invoice->client_state, 'code' => $invoice->client_state_code] = $profile->placeOfSupply();
        $invoice->client_postal_code = $profile->postal_code;
        $invoice->client_country = $profile->country;
        $invoice->client_gstin = $profile->gstin;
        $invoice->client_pan = $profile->pan;
        $invoice->place_of_supply = $invoice->client_state;
    }

    private function applyTax(Invoice $invoice, CustomerBillingProfile $profile): void
    {
        $rates = $this->taxCalculator->getRates($profile);

        $invoice->cgst_percent = $rates['cgst'];

        $invoice->sgst_percent = $rates['sgst'];

        $invoice->igst_percent = $rates['igst'];
    }
}
