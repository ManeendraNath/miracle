<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\models\forms\ManualInvoiceItemForm;
use Yii;

final class ManualInvoiceService
{

    public function __construct(
            private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
            private readonly InvoiceNumberService $numberService = new InvoiceNumberService(),
            private readonly InvoicePaymentService $paymentService = new InvoicePaymentService()
    )
    {
        
    }

    public function create(
            Invoice $invoice,
            array $items
    ): Invoice
    {
        if (!$items) {
            throw new \DomainException(
                            'At least one invoice item is required.'
                    );
        }

        if (empty($invoice->user_id)) {
            throw new \DomainException(
                            'A client must be selected.'
                    );
        }

        if (empty($invoice->due_date)) {
            throw new \DomainException(
                            'Invoice due date is required.'
                    );
        }

        $transaction = Yii::$app->db->beginTransaction();

        try {
            $client = User::findOne(
                    (int) $invoice->user_id
            );

            if ($client === null) {
                throw new \DomainException(
                                'Selected client does not exist.'
                        );
            }

            $profile = CustomerBillingProfile::findOne([
                'user_id' => $client->id,
            ]);

            if ($profile === null) {
                throw new \DomainException(
                                'Please complete the client billing '
                                . 'profile before creating an invoice.'
                        );
            }

            $this->applyClientSnapshot(
                    $invoice,
                    $profile
            );

            $this->applyTax(
                    $invoice,
                    $profile
            );

            $subtotal = $this->calculateSubtotal(
                    $items
            );

            if ($subtotal <= 0) {
                throw new \DomainException(
                                'Invoice subtotal must be greater than zero.'
                        );
            }

            $discount = max(
                    0,
                    round(
                            (float) $invoice->discount_amount,
                            2
                    )
            );

            if ($discount > $subtotal) {
                throw new \DomainException(
                                'Invoice discount cannot exceed subtotal.'
                        );
            }

            $taxable = round(
                    $subtotal - $discount,
                    2
            );

            $tax = $this->taxCalculator->calculate($taxable, (float) $invoice->cgst_percent, (float) $invoice->sgst_percent, (float) $invoice->igst_percent);

            if ($tax['total'] <= 0) {
                throw new \DomainException('Invoice total must be greater than zero.');
            }
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
                throw new \RuntimeException('Invoice could not be created: ' . json_encode($invoice->errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            foreach ($items as $itemForm) {
                $this->createItem($invoice, $itemForm);
            }
            $this->numberService->assign($invoice);
            $transaction->commit();
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
        if (!$items) {
            throw new \DomainException('At least one invoice item is required.');
        }
        if ($invoice->status !== Invoice::STATUS_UNPAID) {
            throw new \DomainException('Only unpaid invoices can be modified.');
        }
        $subtotal = $this->calculateSubtotal($items);
        if ($subtotal <= 0) {
            throw new \DomainException('Invoice subtotal must be greater than zero.');
        }
        $discount = max(0, round((float) $invoice->discount_amount, 2));

        if ($discount > $subtotal) {
            throw new \DomainException('Invoice discount cannot exceed subtotal.');
        }

        $dbTransaction = Yii::$app->db->beginTransaction();

        try {
            $invoiceId = Yii::$app->db->createCommand('SELECT id FROM {{%invoice}} WHERE id = :id FOR UPDATE')->bindValue(':id', (int) $invoice->id)->queryScalar();
            $lockedInvoice = $invoiceId !== false ? Invoice::findOne((int) $invoiceId) : null;
            if ($lockedInvoice === null) {
                throw new \RuntimeException('Invoice no longer exists.');
            }

            if ($lockedInvoice->status !== Invoice::STATUS_UNPAID) {
                throw new \DomainException('Invoice status changed while editing. The invoice can no longer be modified.');
            }

            // Recheck captured payments after locking the invoice.
            $capturedAmount = $this->paymentService->getCapturedAmount((int) $lockedInvoice->id);

            if ($capturedAmount > 0) {
                throw new \DomainException('An invoice with captured payments cannot be modified.');
            }

            $hasAutomaticItems = InvoiceItem::find()
                    ->where(['invoice_id' => $lockedInvoice->id])
                    ->andWhere(['not', ['billing_event_id' => null]])
                    ->exists();

            if ($hasAutomaticItems) {
                throw new \DomainException('Automatic renewal invoices cannot be modified manually.');
            }

            $hasArrears = InvoiceItem::find()->where(['invoice_id' => $lockedInvoice->id, 'item_type' => InvoiceItem::TYPE_ARREARS])->exists();

            if ($hasArrears) {
                throw new \DomainException('Invoices containing carried-forward balances cannot be modified manually.');
            }

            $invalidManualItems = InvoiceItem::find()->where(['invoice_id' => $lockedInvoice->id])->andWhere(['<>', 'item_type', InvoiceItem::TYPE_CUSTOM])->exists();

            if ($invalidManualItems) {
                throw new \DomainException('Only fully manual invoices can be modified using this editor.');
            }

            $existingItems = InvoiceItem::find()->where(['invoice_id' => $lockedInvoice->id, 'item_type' => InvoiceItem::TYPE_CUSTOM])->orderBy(['id' => SORT_ASC])->all();

            foreach ($existingItems as $existingItem) {
                if ($existingItem->delete() === false) {
                    throw new \RuntimeException('Existing manual invoice item could not be removed.');
                }
            }

            foreach ($items as $itemForm) {
                $this->createItem($lockedInvoice, $itemForm);
            }

            $taxable = round($subtotal - $discount, 2);

            $tax = $this->taxCalculator
                    ->calculate(
                            $taxable,
                            (float) $lockedInvoice->cgst_percent,
                            (float) $lockedInvoice->sgst_percent,
                            (float) $lockedInvoice->igst_percent
                    );

            if ($tax['total'] <= 0) {
                throw new \DomainException(
                                'Invoice total must be greater than zero.'
                        );
            }

            $lockedInvoice->discount_amount = $discount;

            $lockedInvoice->coupon_code = $invoice->coupon_code;

            $lockedInvoice->due_date = $invoice->due_date;

            $lockedInvoice->subtotal_amount = $subtotal;

            $lockedInvoice->total_payable = $tax['total'];

            $lockedInvoice->updated_at = time();

            if (!$lockedInvoice->save()) {
                throw new \RuntimeException(
                                'Invoice could not be updated: '
                                . json_encode(
                                        $lockedInvoice->errors,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                )
                        );
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

    private function calculateSubtotal(
            array $items
    ): float
    {
        $subtotal = 0.00;

        foreach ($items as $item) {
            if (!$item instanceof ManualInvoiceItemForm) {
                throw new \InvalidArgumentException(
                                'Invalid manual invoice item.'
                        );
            }

            if (!$item->validate()) {
                throw new \DomainException(
                                'Invalid invoice item: '
                                . json_encode(
                                        $item->errors,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                )
                        );
            }

            $subtotal += $item->getTotal();
        }

        return round(
                $subtotal,
                2
        );
    }

    private function createItem(
            Invoice $invoice,
            ManualInvoiceItemForm $form
    ): void
    {
        $item = new InvoiceItem();

        $item->invoice_id = (int) $invoice->id;

        $item->item_type = InvoiceItem::TYPE_CUSTOM;

        $item->item_id = null;

        $item->billing_event_id = null;

        $item->source_invoice_id = null;

        $item->category = $form->category;

        $item->description = $form->description;

        $item->quantity = $form->quantity;

        $item->unit_price = number_format(
                $form->unit_price,
                2,
                '.',
                ''
        );

        $item->total_price = number_format(
                $form->getTotal(),
                2,
                '.',
                ''
        );

        if (!$item->save()) {
            throw new \RuntimeException(
                            'Invoice item could not be saved: '
                            . json_encode(
                                    $item->errors,
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )
                    );
        }
    }

    private function applyClientSnapshot(
            Invoice $invoice,
            CustomerBillingProfile $profile
    ): void
    {
        $invoice->client_name = $profile->legal_name;
        $invoice->client_address_line_1 = $profile->address_line_1;
        $invoice->client_address_line_2 = $profile->address_line_2;
        $invoice->client_city = $profile->city;
        $invoice->client_state = $profile->state;
        $invoice->client_state_code = $profile->state_code;
        $invoice->client_postal_code = $profile->postal_code;
        $invoice->client_gstin = $profile->gstin;
        $invoice->client_pan = $profile->pan;
        $invoice->place_of_supply = $profile->state;
    }

    private function applyTax(
            Invoice $invoice,
            CustomerBillingProfile $profile
    ): void
    {
        $rates = $this->taxCalculator
                ->getRates(
                        $profile
                );

        $invoice->cgst_percent = $rates['cgst'];

        $invoice->sgst_percent = $rates['sgst'];

        $invoice->igst_percent = $rates['igst'];
    }
}
