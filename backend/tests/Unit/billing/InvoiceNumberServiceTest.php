<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\Invoice;
use common\models\User;
use common\services\billing\InvoiceNumberService;
use Yii;

/** GST invoice serials: per financial year, consecutive, no gaps after a rollback. */
final class InvoiceNumberServiceTest extends Unit
{
    public function testFinancialYearRunsAprilToMarch(): void
    {
        $this->assertSame('2627', InvoiceNumberService::financialYear(strtotime('2026-04-01 00:00:00')));
        $this->assertSame('2627', InvoiceNumberService::financialYear(strtotime('2027-03-31 23:59:59')));
        $this->assertSame('2526', InvoiceNumberService::financialYear(strtotime('2026-03-31 23:59:59')));
        $this->assertSame('9900', InvoiceNumberService::financialYear(strtotime('2099-12-31')));
    }

    public function testSerialsAreConsecutiveAndARolledBackInvoiceReturnsItsNumber(): void
    {
        $db = Yii::$app->db;
        $outer = $db->beginTransaction();
        try {
            $user = $this->user();
            // A far-off year so the real counter isn't touched.
            $createdAt = strtotime('2036-06-01 10:00:00');

            $first = $this->numbered($user, $createdAt);
            $savepoint = $db->beginTransaction(); // nested = savepoint
            $this->numbered($user, $createdAt);
            $savepoint->rollBack();
            $second = $this->numbered($user, $createdAt);

            $this->assertSame('MWT3637-00001', $first);
            $this->assertSame('MWT3637-00002', $second);
            $this->assertLessThanOrEqual(16, strlen($second));
        } finally {
            $outer->rollBack();
        }
    }

    private function numbered(User $user, int $createdAt): string
    {
        $service = new InvoiceNumberService();
        $invoice = new Invoice();
        $invoice->setAttributes(['user_id' => $user->id, 'invoice_number' => $service->createTemporaryNumber(), 'client_name' => 'Serial Test', 'subtotal_amount' => 100, 'discount_amount' => 0, 'total_payable' => 100, 'status' => Invoice::STATUS_UNPAID, 'created_at' => $createdAt, 'updated_at' => $createdAt], false);
        $this->assertTrue($invoice->save(false));
        $service->assign($invoice);

        return $invoice->invoice_number;
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(4));
        $user = new User();
        $user->username = 'serial-' . $suffix;
        $user->email = 'serial-' . $suffix . '@example.test';
        $user->password = 'SerialTestPassword';
        $user->status = User::getStatusActive();
        $user->created_at = $user->updated_at = time();
        $this->assertTrue($user->save(), json_encode($user->errors));

        return $user;
    }
}
