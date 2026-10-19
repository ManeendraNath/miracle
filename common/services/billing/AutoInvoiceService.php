<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\BillingEvent;
use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use Yii;

final class AutoInvoiceService
{

    public function __construct(
            private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
            private readonly InvoiceNumberService $numberService = new InvoiceNumberService(),
            private readonly OutstandingBalanceService $outstandingService = new OutstandingBalanceService()
    )
    {
        
    }

    public function generatePendingInvoices(
            int $runId
    ): array
    {
        if ($runId <= 0) {
            throw new \InvalidArgumentException(
                            'A valid billing run ID is required.'
                    );
        }

        $userIds = BillingEvent::find()
                ->select('user_id')
                ->where([
                    'billing_run_id' => $runId,
                    'status' => BillingEvent::STATUS_PENDING,
                    'invoice_id' => null,
                ])
                ->distinct()
                ->orderBy(['user_id' => SORT_ASC])
                ->column();

        $invoices = [];

        foreach ($userIds as $userId) {
            $invoice = $this->generateForUser($runId, (int) $userId);
            if ($invoice !== null) {
                $invoices[] = $invoice;
            }
        }
        return $invoices;
    }

    public function generateForUser(int $runId, int $userId): ?Invoice
    {
        if ($runId <= 0) {
            throw new \InvalidArgumentException('A valid billing run ID is required.');
        }
        if ($userId <= 0) {
            throw new \InvalidArgumentException('A valid client ID is required.');
        }
        $dbTransaction = Yii::$app->db->beginTransaction();
        try {
            $events = BillingEvent::find()
                    ->where([
                        'billing_run_id' => $runId,
                        'user_id' => $userId,
                        'status' => BillingEvent::STATUS_PENDING,
                        'invoice_id' => null,
                    ])
                    ->orderBy(['due_date' => SORT_ASC, 'id' => SORT_ASC])
                    ->forUpdate()
                    ->all();

            if (!$events) {
                $dbTransaction->commit();
                return null;
            }
            $user = User::findOne($userId);
            if ($user === null) {
                throw new \RuntimeException('Client does not exist for ' . 'automatic invoice generation.');
            }

            $profile = CustomerBillingProfile::findOne(['user_id' => $userId]);

            if ($profile === null) {
                throw new \DomainException(
                                'Customer billing profile is missing '
                                . 'for user ID '
                                . $userId
                                . '.'
                        );
            }

            $invoice = $this->createInvoiceHeader(
                    $runId,
                    $user,
                    $profile,
                    $events
            );

            foreach ($events as $event) {
                $this->createInvoiceItem(
                        $invoice,
                        $event
                );
            }

            $this->outstandingService
                    ->transferForUser(
                            $invoice
                    );

            $this->calculateInvoiceTotals(
                    $invoice
            );

            foreach ($events as $event) {
                $event->invoice_id = $invoice->id;

                $event->status = BillingEvent::STATUS_INVOICED;

                $event->updated_at = time();

                if (!$event->save()) {
                    throw new \RuntimeException(
                                    'Unable to link billing event '
                                    . $event->id
                                    . ' to invoice: '
                                    . json_encode(
                                            $event->errors,
                                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                    )
                            );
                }
            }

            $this->numberService->assign(
                    $invoice
            );

            $dbTransaction->commit();

            $invoice->refresh();

            return $invoice;
        } catch (\Throwable $exception) {
            if ($dbTransaction->isActive) {
                $dbTransaction->rollBack();
            }

            throw $exception;
        }
    }

    private function createInvoiceHeader(
            int $runId,
            User $user,
            CustomerBillingProfile $profile,
            array $events
    ): Invoice
    {
        $rates = $this->taxCalculator
                ->getRates(
                        $profile
                );

        $now = time();

        $invoice = new Invoice();

        $invoice->billing_run_id = $runId;

        $invoice->user_id = (int) $user->id;

        $invoice->invoice_number = $this->numberService
                ->createTemporaryNumber();

        $invoice->client_name = (string) $profile->legal_name;

        $invoice->client_address_line_1 = $profile->address_line_1;

        $invoice->client_address_line_2 = $profile->address_line_2;

        $invoice->client_city = $profile->city;

        $invoice->client_state = $profile->state;

        $invoice->client_state_code = $profile->state_code;

        $invoice->client_postal_code = $profile->postal_code;

        $invoice->client_gstin = $profile->gstin;

        $invoice->client_pan = $profile->pan;

        $invoice->place_of_supply = $profile->state;

        $invoice->subtotal_amount = 0.00;

        $invoice->discount_amount = 0.00;

        $invoice->cgst_percent = $rates['cgst'];

        $invoice->sgst_percent = $rates['sgst'];

        $invoice->igst_percent = $rates['igst'];

        $invoice->total_payable = 0.00;

        $invoice->status = Invoice::STATUS_UNPAID;

        $invoice->due_date = $this->getEarliestDueDate(
                $events
        );

        $invoice->created_at = $now;

        $invoice->updated_at = $now;

        if (!$invoice->save()) {
            throw new \RuntimeException(
                            'Unable to create automatic invoice: '
                            . json_encode(
                                    $invoice->errors,
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )
                    );
        }

        return $invoice;
    }

    private function createInvoiceItem(
            Invoice $invoice,
            BillingEvent $event
    ): void
    {
        if ((float) $event->amount <= 0) {
            throw new \DomainException(
                            'Billing event amount must be greater than zero.'
                    );
        }

        $typeName = InvoiceItem::getTypes()[
                (int) $event->item_type
                ] ?? 'Service';

        $item = new InvoiceItem();

        $item->invoice_id = (int) $invoice->id;

        $item->item_type = (int) $event->item_type;

        $item->item_id = (int) $event->item_id;

        $item->billing_event_id = (int) $event->id;

        $item->source_invoice_id = null;

        $item->category = $typeName
                . ' Renewal';

        $item->description = sprintf(
                '%s | Renewal period: %s to %s',
                $event->description,
                $event->period_start,
                $event->period_end
        );

        $item->quantity = 1;

        $item->unit_price = number_format(
                (float) $event->amount,
                2,
                '.',
                ''
        );

        $item->total_price = number_format(
                (float) $event->amount,
                2,
                '.',
                ''
        );

        if (!$item->save()) {
            throw new \RuntimeException(
                            'Unable to create automatic invoice item: '
                            . json_encode(
                                    $item->errors,
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )
                    );
        }
    }

    private function calculateInvoiceTotals(
            Invoice $invoice
    ): void
    {
        $subtotal = (float) InvoiceItem::find()
                        ->where([
                            'invoice_id' =>
                            $invoice->id,
                        ])
                        ->sum('total_price');

        $subtotal = round(
                $subtotal,
                2
        );

        if ($subtotal <= 0) {
            throw new \DomainException(
                            'Automatic invoice subtotal '
                            . 'must be greater than zero.'
                    );
        }

        $arrearsTotal = (float) InvoiceItem::find()
                        ->where([
                            'invoice_id' =>
                            $invoice->id,
                            'item_type' =>
                            InvoiceItem::TYPE_ARREARS,
                        ])
                        ->sum('total_price');

        $arrearsTotal = round(
                $arrearsTotal,
                2
        );

        $currentCharges = round(
                $subtotal - $arrearsTotal,
                2
        );

        if ($currentCharges < 0) {
            throw new \RuntimeException(
                            'Invoice current-charge subtotal '
                            . 'cannot be negative.'
                    );
        }

        $discount = max(
                0,
                round(
                        (float) $invoice
                        ->discount_amount,
                        2
                )
        );

        if ($discount > $currentCharges) {
            throw new \DomainException(
                            'Invoice discount cannot exceed '
                            . 'current taxable charges.'
                    );
        }

        $taxableAmount = round(
                $currentCharges - $discount,
                2
        );

        $tax = $this->taxCalculator
                ->calculate(
                        $taxableAmount,
                        (float) $invoice
                        ->cgst_percent,
                        (float) $invoice
                        ->sgst_percent,
                        (float) $invoice
                        ->igst_percent
                );

        $invoice->subtotal_amount = $subtotal;

        $invoice->total_payable = round(
                $tax['total'] + $arrearsTotal,
                2
        );

        $invoice->updated_at = time();

        if (!$invoice->save()) {
            throw new \RuntimeException(
                            'Unable to calculate automatic '
                            . 'invoice totals: '
                            . json_encode(
                                    $invoice->errors,
                                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                            )
                    );
        }
    }

    private function getEarliestDueDate(
            array $events
    ): string
    {
        if (!$events) {
            throw new \LogicException(
                            'At least one billing event '
                            . 'is required.'
                    );
        }

        $dates = [];

        foreach ($events as $event) {
            if (!$event->due_date) {
                throw new \RuntimeException(
                                'Billing event '
                                . $event->id
                                . ' does not have a due date.'
                        );
            }

            $dates[] = (string) $event->due_date;
        }

        sort($dates);

        return $dates[0];
    }
}
