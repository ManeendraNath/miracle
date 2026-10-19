<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;

final class InvoiceNumberService
{

    private const PREFIX = 'MWT';

    public function createTemporaryNumber(): string
    {
        return 'TMP-'
                . strtoupper(
                        bin2hex(
                                random_bytes(16)
                        )
                );
    }

    public function assign(
            Invoice $invoice
    ): void
    {
        if ($invoice->getIsNewRecord()) {
            throw new \LogicException(
                            'Invoice must be saved before '
                            . 'assigning its permanent number.'
                    );
        }

        if ((int) $invoice->id <= 0) {
            throw new \LogicException(
                            'Invoice ID is required before '
                            . 'assigning its permanent number.'
                    );
        }

        $currentNumber = trim(
                (string) $invoice->invoice_number
        );

        if (
                $currentNumber !== '' && !str_starts_with(
                        $currentNumber,
                        'TMP-'
                )
        ) {
            return;
        }

        $createdAt = (int) $invoice->created_at;

        if ($createdAt <= 0) {
            throw new \LogicException(
                            'Invoice creation timestamp is required.'
                    );
        }

        $invoiceNumber = self::PREFIX
                . date(
                        'Ymd',
                        $createdAt
                )
                . $invoice->id;

        $exists = Invoice::find()
                ->where([
                    'invoice_number' =>
                    $invoiceNumber,
                ])
                ->andWhere([
                    '<>',
                    'id',
                    $invoice->id,
                ])
                ->exists();

        if ($exists) {
            throw new \RuntimeException(
                            'Generated invoice number already exists.'
                    );
        }

        $invoice->invoice_number = $invoiceNumber;

        $invoice->updated_at = time();

        if (!$invoice->save(
                        false,
                        [
                            'invoice_number',
                            'updated_at',
                        ]
                )) {
            throw new \RuntimeException(
                            'Unable to assign permanent invoice number.'
                    );
        }
    }
}
