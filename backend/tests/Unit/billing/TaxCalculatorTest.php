<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\services\billing\TaxCalculator;
use Yii;

/** The tax lines shown on invoices/PDF/GST report must match how total_payable was computed. */
final class TaxCalculatorTest extends Unit
{
    public function testCarriedForwardBalanceIsNotTaxedAgain(): void
    {
        $tx = Yii::$app->db->beginTransaction();
        try {
            $user = new User(['username' => 'tax-' . bin2hex(random_bytes(3)), 'email' => bin2hex(random_bytes(3)) . '@example.test', 'password' => 'TaxTestPassword', 'status' => User::getStatusActive()]);
            $this->assertTrue($user->save(), json_encode($user->errors));
            $invoice = new Invoice([
                'user_id' => $user->id, 'invoice_number' => 'TAX-' . bin2hex(random_bytes(3)), 'client_name' => 'T',
                'subtotal_amount' => 1500, 'discount_amount' => 100, 'cgst_percent' => 9, 'sgst_percent' => 9, 'igst_percent' => 0,
                'total_payable' => 1562, 'status' => Invoice::STATUS_UNPAID, 'due_date' => date('Y-m-d'),
                'created_at' => time(), 'updated_at' => time(),
            ]);
            $this->assertTrue($invoice->save(false));
            foreach ([[InvoiceItem::TYPE_HOSTING, 1000], [InvoiceItem::TYPE_ARREARS, 500]] as [$type, $amount]) {
                $item = new InvoiceItem(['invoice_id' => $invoice->id, 'item_type' => $type, 'category' => 'x', 'description' => 'x', 'quantity' => 1, 'unit_price' => $amount, 'total_price' => $amount]);
                $this->assertTrue($item->save(false));
            }

            $tax = (new TaxCalculator())->forInvoice($invoice);

            $this->assertEquals(900.00, $tax['taxable']);
            $this->assertEquals(81.00, $tax['cgstAmount']);
            $this->assertEquals(81.00, $tax['sgstAmount']);
            // Shown lines add up to the stored total: 900 + 81 + 81 + 500 arrears = 1562.
            $this->assertEquals((float) $invoice->total_payable, $tax['taxable'] + $tax['cgstAmount'] + $tax['sgstAmount'] + 500);
        } finally {
            $tx->rollBack();
        }
    }
}
