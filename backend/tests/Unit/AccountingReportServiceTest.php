<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use backend\controllers\BaseController;
use Codeception\Test\Unit;
use common\models\Invoice;
use common\models\Transaction;
use common\models\User;
use common\services\report\AccountingReportService;
use Yii;
use yii\db\Transaction as DbTransaction;

final class AccountingReportServiceTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;
    private string $_prefix;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $this->_prefix = 'RPT' . bin2hex(random_bytes(3)) . '-';
        $this->_user = new User();
        $this->_user->username = 'report-test-' . $this->_prefix;
        $this->_user->email = 'report-' . bin2hex(random_bytes(4)) . '@example.test';
        $this->_user->password = 'ReportTestPassword';
        $this->_user->status = User::getStatusActive();
        $this->_user->created_at = $this->_user->updated_at = time();
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testRangeValidation(): void
    {
        [$start, $end] = AccountingReportService::range('2001-03-01', '2001-03-31');
        $this->assertSame(strtotime('2001-03-01 00:00:00'), $start);
        $this->assertSame(strtotime('2001-04-01 00:00:00'), $end);
        AccountingReportService::range('2024-01-01', '2024-12-31'); // 366 days (leap year) is allowed

        foreach ([['2001-03-31', '2001-03-01'], ['2001-02-30', '2001-03-01'], ['', '2001-03-01'], ['2024-01-01', '2025-01-01']] as [$from, $to]) {
            try {
                AccountingReportService::range($from, $to);
                $this->fail("{$from}..{$to} should be rejected");
            } catch (\DomainException) {
                // expected
            }
        }
    }

    public function testInvoiceRowsFilterByIssueDateAndSplitTax(): void
    {
        $this->invoice('before', '2001-02-28 23:59:59');
        $this->invoice('intra', '2001-03-01 00:00:00', ['subtotal_amount' => 1000, 'discount_amount' => 100, 'cgst_percent' => 9, 'sgst_percent' => 9, 'igst_percent' => 0, 'total_payable' => 1062, 'client_gstin' => '07ABCDE1234F1Z2', 'coupon_code' => 'SAVE10']);
        $this->invoice('inter', '2001-03-15 12:00:00', ['subtotal_amount' => 333.33, 'cgst_percent' => 0, 'sgst_percent' => 0, 'igst_percent' => 18, 'total_payable' => 393.33, 'client_name' => '=HYPERLINK("http://x")']);
        $this->invoice('cancel', '2001-03-31 23:59:59', ['status' => Invoice::STATUS_CANCELLED]);
        $this->invoice('after', '2001-04-01 00:00:00');

        $rows = $this->rows((new AccountingReportService())->invoiceRows(...AccountingReportService::range('2001-03-01', '2001-03-31')));
        $this->assertSame([$this->_prefix . 'intra', $this->_prefix . 'inter', $this->_prefix . 'cancel'], array_keys($rows));

        $intra = $rows[$this->_prefix . 'intra'];
        $this->assertSame('2001-03-01', $intra['Issue Date']);
        $this->assertSame('900.00', $intra['Taxable Value']);
        $this->assertSame(['9.00', '81.00', '9.00', '81.00', '0.00', '0.00'], [$intra['CGST %'], $intra['CGST Amount'], $intra['SGST %'], $intra['SGST Amount'], $intra['IGST %'], $intra['IGST Amount']]);
        $this->assertSame(['07ABCDE1234F1Z2', 'SAVE10', '1062.00'], [$intra['Customer GSTIN'], $intra['Coupon Code'], $intra['Total Payable']]);
        $this->assertSame(['0.00', '1062.00'], [$intra['Arrears Carried In'], $intra['Invoice Value (excl. arrears)']]);

        $inter = $rows[$this->_prefix . 'inter'];
        $this->assertSame(['0.00', '0.00', '60.00'], [$inter['CGST Amount'], $inter['SGST Amount'], $inter['IGST Amount']]);
        $service = new AccountingReportService();
        $csv = implode('', iterator_to_array($service->csv($service->invoiceRows(...AccountingReportService::range('2001-03-15', '2001-03-15'))), false));
        $this->assertStringContainsString(",\"'=HYPERLINK(\"\"http://x\"\")\",", $csv);

        $this->assertSame('Cancelled', $rows[$this->_prefix . 'cancel']['Status']);
    }

    public function testPaymentRowsIncludeCapturedAndRefundedInRange(): void
    {
        $invoice = $this->invoice('pay', '2001-03-01 10:00:00', ['client_name' => 'Acme Pvt Ltd']);
        $this->payment($invoice, 'cap', Transaction::STATUS_CAPTURED, '2001-03-05 10:00:00', Transaction::GATEWAY_RAZORPAY, 2.36);
        $this->payment($invoice, 'ref', Transaction::STATUS_REFUNDED, '2001-03-31 23:00:00', Transaction::GATEWAY_UPI_BANK);
        $this->payment($invoice, 'fail', Transaction::STATUS_FAILED, null, Transaction::GATEWAY_UPI_BANK);
        $this->payment($invoice, 'pend', Transaction::STATUS_PENDING, null, Transaction::GATEWAY_UPI_BANK);
        $this->payment($invoice, 'late', Transaction::STATUS_CAPTURED, '2001-04-01 00:00:00', Transaction::GATEWAY_RAZORPAY);

        $rows = $this->rows((new AccountingReportService())->paymentRows(...AccountingReportService::range('2001-03-01', '2001-03-31')), 'Reference');
        $this->assertSame([$this->_prefix . 'cap', $this->_prefix . 'ref'], array_keys($rows));
        $this->assertSame(
            ['Paid Date' => '2001-03-05', 'Invoice Number' => $this->_prefix . 'pay', 'Customer' => 'Acme Pvt Ltd', 'Method' => 'Razorpay', 'Reference' => $this->_prefix . 'cap', 'Amount' => '500.00', 'Gateway Fee' => '2.36', 'Refunded In Period' => '0.00', 'Last Refund Date In Period' => '', 'Status' => 'Captured'],
            $rows[$this->_prefix . 'cap']
        );
        $this->assertSame(['UPI / Bank Transfer', 'Refunded'], [$rows[$this->_prefix . 'ref']['Method'], $rows[$this->_prefix . 'ref']['Status']]);
    }

    public function testARefundOfAnEarlierPaymentAppearsInTheRefundMonth(): void
    {
        $invoice = $this->invoice('late', '2001-05-01 10:00:00');
        $this->payment($invoice, 'may', Transaction::STATUS_REFUNDED, '2001-05-20 10:00:00', Transaction::GATEWAY_UPI_BANK);
        $refund = new \common\models\TransactionRefund(['transaction_id' => Transaction::findOne(['gateway_payment_id' => $this->_prefix . 'may'])->id, 'amount' => 200, 'refunded_at' => strtotime('2001-06-03 10:00:00')]);
        $this->assertTrue($refund->save(false));
        $june = $this->rows((new AccountingReportService())->paymentRows(...AccountingReportService::range('2001-06-01', '2001-06-30')), 'Reference')[$this->_prefix . 'may'];
        $this->assertSame(['200.00', '2001-06-03'], [$june['Refunded In Period'], $june['Last Refund Date In Period']]);

        foreach ([['2001-05-01', '2001-05-31', 1], ['2001-06-01', '2001-06-30', 1], ['2001-07-01', '2001-07-31', 0]] as [$from, $to, $expected]) {
            $rows = $this->rows((new AccountingReportService())->paymentRows(...AccountingReportService::range($from, $to)), 'Reference');
            $this->assertCount($expected, array_intersect_key($rows, [$this->_prefix . 'may' => 1]), $from);
        }
    }

    public function testCsvHasBomAndGuardsFormulas(): void
    {
        $csv = implode('', iterator_to_array((new AccountingReportService())->csv([['Name', 'Amount'], ['@SUM(A1)', '-5.00'], ['+91 x', 'plain']]), false));
        $this->assertSame("\xEF\xBB\xBFName,Amount\r\n'@SUM(A1),-5.00\r\n\"'+91 x\",plain\r\n", $csv);
        $this->assertSame("'-1+2", AccountingReportService::safeCell('-1+2'));
        $this->assertSame("'\tx", AccountingReportService::safeCell("\tx"));
        $this->assertSame('', AccountingReportService::safeCell(''));
    }

    public function testOnlyAdminAndUpCanOpenReports(): void
    {
        foreach (['index', 'download'] as $action) {
            $this->assertFalse(BaseController::canAccess((object) ['role' => 'Manager'], 'report', $action));
            $this->assertTrue(BaseController::canAccess((object) ['role' => 'Admin'], 'report', $action));
            $this->assertTrue(BaseController::canAccess((object) ['role' => 'Superadmin'], 'report', $action));
        }
    }

    private function invoice(string $number, string $createdAt, array $attributes = []): Invoice
    {
        $invoice = new Invoice(array_merge([
            'user_id' => $this->_user->id,
            'invoice_number' => $this->_prefix . $number,
            'client_name' => 'Report Customer',
            'subtotal_amount' => 100,
            'discount_amount' => 0,
            'cgst_percent' => 0,
            'sgst_percent' => 0,
            'igst_percent' => 0,
            'total_payable' => 100,
            'status' => Invoice::STATUS_UNPAID,
            'created_at' => strtotime($createdAt),
            'updated_at' => strtotime($createdAt),
        ], $attributes));
        $this->assertTrue($invoice->save(false));
        return $invoice;
    }

    private function payment(Invoice $invoice, string $ref, string $status, ?string $paidAt, int $gateway, float $fee = 0.0): void
    {
        $payment = new Transaction([
            'invoice_id' => $invoice->id,
            'gateway_id' => $gateway,
            'gateway_payment_id' => $this->_prefix . $ref,
            'amount' => 500,
            'gateway_fee' => $fee,
            'status' => $status,
            'paid_at' => $paidAt === null ? null : strtotime($paidAt),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
        $this->assertTrue($payment->save(false));
    }

    /** Rows keyed by $keyColumn (only this test's rows), each as header => value. */
    private function rows(\Generator $generator, string $keyColumn = 'Invoice Number'): array
    {
        $header = $generator->current();
        $rows = [];
        for ($generator->next(); $generator->valid(); $generator->next()) {
            $row = array_combine($header, $generator->current());
            if (str_starts_with($row[$keyColumn], $this->_prefix)) {
                $rows[$row[$keyColumn]] = $row;
            }
        }
        return $rows;
    }
}
