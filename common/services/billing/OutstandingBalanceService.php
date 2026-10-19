<?php

declare(strict_types=1);

namespace common\services\billing;

use Yii;
use common\models\Invoice;
use common\models\InvoiceItem;

final class OutstandingBalanceService
{

    public function __construct(
            private readonly InvoicePaymentService $paymentService = new InvoicePaymentService()
    )
    {
        
    }

    public function transferForUser(
            Invoice $targetInvoice
    ): float
    {
        if ($targetInvoice->getIsNewRecord()) {
            throw new \LogicException(
                            'Target invoice must be saved before '
                            . 'outstanding balances are transferred.'
                    );
        }

        if ((int) $targetInvoice->user_id <= 0) {
            throw new \LogicException(
                            'Target invoice must belong to a valid client.'
                    );
        }
        if ($targetInvoice->billing_run_id === null) {
            throw new \LogicException(
                            'Automatic target invoice must belong to a billing run.'
                    );
        }
        $sourceInvoiceIds = Yii::$app->db
                ->createCommand(
                        '
        SELECT id
        FROM {{%invoice}}
        WHERE user_id = :userId
          AND id <> :targetInvoiceId
          AND status IN (:unpaid, :partiallyPaid)
          AND (
              billing_run_id IS NULL
              OR billing_run_id <> :billingRunId
          )
        ORDER BY created_at ASC, id ASC
        FOR UPDATE
        '
                )
                ->bindValues([
                    ':userId' =>
                    (int) $targetInvoice->user_id,
                    ':targetInvoiceId' =>
                    (int) $targetInvoice->id,
                    ':unpaid' =>
                    Invoice::STATUS_UNPAID,
                    ':partiallyPaid' =>
                    Invoice::STATUS_PARTIALLY_PAID,
                    ':billingRunId' =>
                    (int) $targetInvoice
                    ->billing_run_id,
                ])
                ->queryColumn();

        $sourceInvoices = $sourceInvoiceIds ? Invoice::find()
                        ->where([
                            'id' => $sourceInvoiceIds,
                        ])
                        ->orderBy([
                            'created_at' => SORT_ASC,
                            'id' => SORT_ASC,
                        ])
                        ->all() : [];

        $transferredTotal = 0.00;

        foreach ($sourceInvoices as $sourceInvoice) {
            $alreadyTransferred = InvoiceItem::find()
                    ->where([
                        'item_type' =>
                        InvoiceItem::TYPE_ARREARS,
                        'source_invoice_id' =>
                        $sourceInvoice->id,
                    ])
                    ->exists();

            if ($alreadyTransferred) {
                continue;
            }

            $outstanding = $this->paymentService
                    ->getOutstanding(
                            $sourceInvoice
                    );

            if ($outstanding <= 0) {
                continue;
            }

            $item = new InvoiceItem();

            $item->invoice_id = (int) $targetInvoice->id;

            $item->item_type = InvoiceItem::TYPE_ARREARS;

            $item->item_id = null;

            $item->billing_event_id = null;

            $item->source_invoice_id = (int) $sourceInvoice->id;

            $item->category = 'Previous Outstanding';

            $item->description = 'Previous outstanding balance from invoice '
                    . $sourceInvoice->invoice_number;

            $item->quantity = 1;

            $item->unit_price = number_format(
                    $outstanding,
                    2,
                    '.',
                    ''
            );

            $item->total_price = number_format(
                    $outstanding,
                    2,
                    '.',
                    ''
            );

            if (!$item->save()) {
                throw new \RuntimeException(
                                'Unable to carry forward balance from invoice '
                                . $sourceInvoice->invoice_number
                                . ': '
                                . json_encode(
                                        $item->errors,
                                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                                )
                        );
            }

            $sourceInvoice->status = Invoice::STATUS_CARRIED_FORWARD;

            $sourceInvoice->updated_at = time();

            if (!$sourceInvoice->save(
                            false,
                            [
                                'status',
                                'updated_at',
                            ]
                    )) {
                throw new \RuntimeException(
                                'Unable to mark source invoice '
                                . 'as carried forward.'
                        );
            }

            $transferredTotal += $outstanding;
        }

        return round(
                $transferredTotal,
                2
        );
    }
}
