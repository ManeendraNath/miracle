<?php

declare(strict_types=1);

namespace common\services\report;

use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\Transaction;
use common\services\billing\TaxCalculator;
use DateTimeImmutable;

/**
 * CSV exports for the accountant (GST filing): invoices issued and payments captured in a date range.
 */
final class AccountingReportService
{
    public const MAX_RANGE_DAYS = 366;

    private const GATEWAYS = [
        Transaction::GATEWAY_MANUAL => 'Manual',
        Transaction::GATEWAY_RAZORPAY => 'Razorpay',
        Transaction::GATEWAY_UPI_BANK => 'UPI / Bank Transfer',
    ];

    public function __construct(
        private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
    ) {
    }

    /**
     * Validates Y-m-d dates (app timezone) and returns [start, end) as Unix timestamps.
     *
     * @return array{int, int}
     */
    public static function range(string $from, string $to): array
    {
        $start = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        $end = DateTimeImmutable::createFromFormat('!Y-m-d', $to);
        if ($start === false || $end === false || $start->format('Y-m-d') !== $from || $end->format('Y-m-d') !== $to) {
            throw new \DomainException('Enter both dates as YYYY-MM-DD.');
        }
        if ($start > $end) {
            throw new \DomainException('"From" must be on or before "To".');
        }
        if ($start->diff($end)->days >= self::MAX_RANGE_DAYS) {
            throw new \DomainException('The range can be at most ' . self::MAX_RANGE_DAYS . ' days.');
        }

        return [$start->getTimestamp(), $end->modify('+1 day')->getTimestamp()];
    }

    /** Header row, then one row per invoice created in [$start, $end), cancelled ones included. */
    public function invoiceRows(int $start, int $end): \Generator
    {
        yield [
            'Invoice Number', 'Issue Date', 'Due Date', 'Customer Name', 'Customer GSTIN', 'Customer State', 'Customer Country',
            'State Code', 'Place of Supply', 'Subtotal', 'Discount', 'Taxable Value', 'CGST %', 'CGST Amount',
            'SGST %', 'SGST Amount', 'IGST %', 'IGST Amount', 'Total Payable', 'Status', 'Coupon Code',
            // Total Payable includes balances carried in from older invoices, which are also exported
            // (as Carried Forward): sum Invoice Value, not Total Payable, to avoid counting them twice.
            'Arrears Carried In', 'Invoice Value (excl. arrears)',
        ];
        $query = Invoice::find()
            ->where(['>=', 'created_at', $start])
            ->andWhere(['<', 'created_at', $end])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC]);
        foreach ($query->each(500) as $invoice) {
            /** @var Invoice $invoice */
            $tax = $this->taxCalculator->forInvoice($invoice);
            yield [
                $invoice->invoice_number,
                date('Y-m-d', (int) $invoice->created_at),
                (string) $invoice->due_date,
                $invoice->client_name,
                (string) $invoice->client_gstin,
                (string) $invoice->client_state,
                (string) ($invoice->client_country ?: CustomerBillingProfile::COUNTRY_INDIA),
                (string) $invoice->client_state_code,
                (string) $invoice->place_of_supply,
                self::money($invoice->subtotal_amount),
                self::money($invoice->discount_amount),
                self::money($tax['taxable']),
                self::money($invoice->cgst_percent),
                self::money($tax['cgstAmount']),
                self::money($invoice->sgst_percent),
                self::money($tax['sgstAmount']),
                self::money($invoice->igst_percent),
                self::money($tax['igstAmount']),
                self::money($invoice->total_payable),
                $invoice->statusLabel(),
                (string) $invoice->coupon_code,
                self::money(round((float) $invoice->total_payable - $tax['total'], 2)),
                self::money($tax['total']),
            ];
        }
    }

    /**
     * Header row, then one row per payment captured (paid_at) or refunded (refunded_at) in [$start, $end), so a
     * refund of an earlier month's payment still shows up: that payment then appears in both months' files, with
     * its original Paid Date. Refunded To Date is the running total at download time and Last Refund Date the latest
     * one (status Refunded when all of it went back), so don't add that column up across months.
     */
    public function paymentRows(int $start, int $end): \Generator
    {
        yield ['Paid Date', 'Invoice Number', 'Customer', 'Method', 'Reference', 'Amount', 'Gateway Fee', 'Refunded To Date', 'Last Refund Date', 'Status'];
        $query = Transaction::find()
            ->with('invoice')
            ->where(['status' => [Transaction::STATUS_CAPTURED, Transaction::STATUS_REFUNDED]])
            ->andWhere(['or', ['and', ['>=', 'paid_at', $start], ['<', 'paid_at', $end]], ['and', ['>=', 'refunded_at', $start], ['<', 'refunded_at', $end]]])
            ->orderBy(['paid_at' => SORT_ASC, 'id' => SORT_ASC]);
        foreach ($query->each(500) as $payment) {
            /** @var Transaction $payment */
            yield [
                date('Y-m-d', (int) $payment->paid_at),
                (string) $payment->invoice?->invoice_number,
                (string) $payment->invoice?->client_name,
                self::GATEWAYS[(int) $payment->gateway_id] ?? 'Other',
                (string) $payment->gateway_payment_id,
                self::money($payment->amount),
                self::money($payment->gateway_fee),
                self::money($payment->refunded_amount),
                $payment->refunded_at ? date('Y-m-d', (int) $payment->refunded_at) : '',
                $payment->isUnapplied() ? $payment->status . ' (unapplied)' : $payment->status,
            ];
        }
    }

    /**
     * Encodes rows as UTF-8 CSV (with BOM for Excel), yielding ~64 KB chunks for Response::$stream.
     *
     * @param iterable<array<string>> $rows
     */
    public function csv(iterable $rows): \Generator
    {
        $buffer = fopen('php://temp', 'w+b');
        fwrite($buffer, "\xEF\xBB\xBF");
        foreach ($rows as $row) {
            fputcsv($buffer, array_map(self::safeCell(...), $row), ',', '"', '', "\r\n");
            if (ftell($buffer) >= 65536) {
                yield self::drain($buffer);
            }
        }
        yield self::drain($buffer);
        fclose($buffer);
    }

    /** Stops spreadsheet apps from running text cells as formulas (CSV injection); numbers pass through. */
    public static function safeCell(string $value): string
    {
        if ($value !== '' && !is_numeric($value) && str_contains("=+-@\t\r", $value[0])) {
            return "'" . $value;
        }
        return $value;
    }

    private static function money(mixed $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /** @param resource $buffer */
    private static function drain($buffer): string
    {
        rewind($buffer);
        $chunk = (string) stream_get_contents($buffer);
        ftruncate($buffer, 0);
        rewind($buffer);
        return $chunk;
    }
}
