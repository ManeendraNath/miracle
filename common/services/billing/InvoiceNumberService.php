<?php

declare(strict_types=1);

namespace common\services\billing;

use common\models\Invoice;

final class InvoiceNumberService
{
    private const PREFIX = 'MWT';

    public function createTemporaryNumber(): string
    {
        return 'TMP-' . strtoupper(bin2hex(random_bytes(16)));
    }

    public function assign(Invoice $invoice): void
    {
        if ($invoice->getIsNewRecord()) {
            throw new \LogicException('Invoice must be saved before ' . 'assigning its permanent number.');
        }

        if ((int) $invoice->id <= 0) {
            throw new \LogicException('Invoice ID is required before ' . 'assigning its permanent number.');
        }

        $currentNumber = trim((string) $invoice->invoice_number);

        if ($currentNumber !== '' && !str_starts_with($currentNumber, 'TMP-')) {
            return;
        }

        $createdAt = (int) $invoice->created_at;

        if ($createdAt <= 0) {
            throw new \LogicException('Invoice creation timestamp is required.');
        }

        $invoiceNumber = self::PREFIX . self::financialYear($createdAt) . '-' . sprintf('%05d', $this->nextSerial(self::financialYear($createdAt)));

        $exists = Invoice::find()
            ->where([
                'invoice_number' => $invoiceNumber,
            ])
            ->andWhere(['<>', 'id', $invoice->id])
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Generated invoice number already exists.');
        }

        $invoice->invoice_number = $invoiceNumber;

        $invoice->updated_at = time();

        if (!$invoice->save(false, ['invoice_number', 'updated_at'])) {
            throw new \RuntimeException('Unable to assign permanent invoice number.');
        }
    }

    /** Indian financial year (April-March) as 4 digits: 2026-04-01 .. 2027-03-31 -> "2627". */
    public static function financialYear(int $timestamp): string
    {
        $year = (int) date('Y', $timestamp);
        $start = (int) date('n', $timestamp) >= 4 ? $year : $year - 1;

        return sprintf('%02d%02d', $start % 100, ($start + 1) % 100);
    }

    /**
     * Next serial in the year. Must run inside the invoice's DB transaction: the row lock
     * serialises concurrent invoices, and a rollback returns the number (no gaps).
     */
    private function nextSerial(string $financialYear): int
    {
        $db = Invoice::getDb();
        if ($db->getTransaction() === null) {
            throw new \LogicException('Invoice numbers must be assigned inside the invoice transaction.');
        }
        $db->createCommand('INSERT IGNORE INTO {{%invoice_sequence}} (financial_year, last_number) VALUES (:fy, 0)', [':fy' => $financialYear])->execute();
        $last = (int) $db->createCommand('SELECT last_number FROM {{%invoice_sequence}} WHERE financial_year = :fy FOR UPDATE', [':fy' => $financialYear])->queryScalar();
        $db->createCommand()->update('{{%invoice_sequence}}', ['last_number' => $last + 1], ['financial_year' => $financialYear])->execute();

        return $last + 1;
    }
}
