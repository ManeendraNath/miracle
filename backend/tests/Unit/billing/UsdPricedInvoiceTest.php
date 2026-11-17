<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\models\forms\ManualInvoiceItemForm;
use common\services\billing\InvoiceCouponService;
use common\services\billing\ManualInvoiceService;
use common\services\payment\ExchangeRateService;
use common\services\payment\PayPalService;
use Yii;
use yii\db\Transaction as DbTransaction;
use yii\httpclient\MockTransport;
use yii\httpclient\Response;

/** Invoices priced in US dollars for clients abroad: dollars for the client, rupees in the books. */
final class UsdPricedInvoiceTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;
    private CustomerBillingProfile $_profile;
    private MockTransport $_http;

    protected function _before(): void
    {
        parent::_before();
        Yii::$app->params['billing']['gst'] = ['cgst' => 9.00, 'sgst' => 9.00, 'igst' => 18.00];
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $this->_http = new MockTransport();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User(['username' => 'usd-' . $suffix, 'email' => 'usd-' . $suffix . '@example.test', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $this->_user->password = 'UsdTestPassword1';
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
        $this->_profile = new CustomerBillingProfile(['user_id' => $this->_user->id, 'legal_name' => 'Maple Ltd', 'address_line_1' => '1 King St', 'city' => 'Toronto', 'country' => 'Canada', 'postal_code' => 'M5H', 'is_gst_applicable' => 0, 'created_at' => time(), 'updated_at' => time()]);
        $this->assertTrue($this->_profile->save(), json_encode($this->_profile->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction?->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testDollarPricesBecomeRupeesAtTheReferenceRateAndConvertBackExactly(): void
    {
        $this->rateIs(88.1234, '2026-11-14');
        $invoice = $this->service()->create($this->invoice(), [$this->item(1, 1500.00), $this->item(2, 249.99)]);

        $this->assertTrue($invoice->isUsdPriced());
        $this->assertSame('1999.98', number_format((float) $invoice->usd_total, 2, '.', ''));
        $this->assertSame('2026-11-14', $invoice->usd_rate_date, 'the rate of the last working day on or before the invoice date');
        $this->assertSame(ExchangeRateService::REFERENCE_SOURCE, $invoice->usd_rate_source);
        $this->assertEqualsWithDelta(0.0, (float) $invoice->usd_fee_percent_locked + (float) $invoice->usd_fee_fixed_locked, 0.001, 'no PayPal fee on top');
        $this->assertNull($invoice->usd_rate_until, 'the rate never expires');
        $this->assertEquals(0.0, (float) $invoice->igst_percent, 'export of services: no GST');
        // 1500 x 88.1234 = 132185.10; 249.99 x 88.1234 = 22029.97 (x 2 = 44059.94)
        $this->assertEquals(176245.04, (float) $invoice->total_payable);
        $this->assertSame((float) $invoice->usd_total, $invoice->usdFor((float) $invoice->total_payable));
        $lines = InvoiceItem::find()->where(['invoice_id' => $invoice->id])->orderBy(['id' => SORT_ASC])->all();
        $this->assertEquals([249.99, 499.98], [(float) $lines[1]->usd_unit_price, (float) $lines[1]->usd_total_price]);
        $this->assertEquals([22029.97, 44059.94], [(float) $lines[1]->unit_price, (float) $lines[1]->total_price]);
    }

    public function testEveryRupeeTotalConvertsBackToTheExactDollarTotal(): void
    {
        mt_srand(20261117);
        for ($i = 0; $i < 40; $i++) {
            $rate = round(mt_rand(7000000, 9900000) / 100000, 4);
            $this->rateIs($rate, '2026-11-14');
            $items = [];
            for ($n = mt_rand(1, 8); $n > 0; $n--) {
                $items[] = $this->item(mt_rand(1, 9), mt_rand(1, 999999) / 100);
            }
            $invoice = $this->service()->create($this->invoice(), $items);
            $this->assertSame((float) $invoice->usd_total, $invoice->usdFor((float) $invoice->total_payable), "rate $rate");
        }
    }

    public function testOnlyForClientsAbroad(): void
    {
        $this->_profile->updateAttributes(['country' => CustomerBillingProfile::COUNTRY_INDIA]);
        $this->rateIs(88.0, '2026-11-14');
        $this->expectExceptionMessage('clients abroad only');
        $this->service()->create($this->invoice(), [$this->item(1, 100.00)]);
    }

    public function testNoCouponOrDiscount(): void
    {
        $this->rateIs(88.0, '2026-11-14');
        $invoice = $this->invoice();
        $invoice->discount_amount = 10.00;
        $this->expectExceptionMessage('no coupon or discount');
        $this->service()->create($invoice, [$this->item(1, 100.00)]);
    }

    public function testNoRateNoInvoice(): void
    {
        $this->_http->appendResponse(new Response(['content' => '{}', 'headers' => ['http-code' => '503', 'content-type' => 'application/json']]));
        try {
            $this->service()->create($this->invoice(), [$this->item(1, 100.00)]);
            $this->fail('An invoice was priced without a rate.');
        } catch (\DomainException $e) {
            $this->assertStringContainsString('exchange rate could not be fetched', $e->getMessage());
        }
        $this->assertFalse(Invoice::find()->where(['user_id' => $this->_user->id])->exists());
    }

    public function testAnEditKeepsTheRateAndAClientNowInIndiaIsRefused(): void
    {
        $this->rateIs(88.0, '2026-11-14');
        $invoice = $this->service()->create($this->invoice(), [$this->item(1, 100.00)]);
        // No rate is fetched for the edit (none is queued): the invoice keeps the one of its date.
        $edit = Invoice::findOne($invoice->id);
        $updated = $this->service()->update($edit, [$this->item(2, 100.00)]);
        $this->assertEquals([200.00, 17600.00, 88.0], [(float) $updated->usd_total, (float) $updated->total_payable, (float) $updated->usd_rate_locked]);

        $this->_profile->updateAttributes(['country' => CustomerBillingProfile::COUNTRY_INDIA]);
        $this->expectExceptionMessage('clients abroad only');
        $this->service()->update(Invoice::findOne($invoice->id), [$this->item(3, 100.00)]);
    }

    public function testSwitchingAnIndianEraInvoiceToDollarsTakesTheClientAndTaxAsTheyAreNow(): void
    {
        $this->_profile->updateAttributes(['country' => CustomerBillingProfile::COUNTRY_INDIA, 'state' => 'Maharashtra', 'state_code' => '27', 'is_gst_applicable' => 1]);
        $rupees = $this->invoice();
        $rupees->priceCurrency = Invoice::CURRENCY_INR;
        $invoice = $this->service()->create($rupees, [$this->item(1, 1000.00)]);
        $this->assertEquals(18.0, (float) $invoice->igst_percent);

        $this->_profile->updateAttributes(['country' => 'Canada', 'is_gst_applicable' => 0]);
        $this->rateIs(84.0, '2026-11-14');
        $edit = Invoice::findOne($invoice->id);
        $edit->priceCurrency = Invoice::CURRENCY_USD;
        $updated = $this->service()->update($edit, [$this->item(1, 100.00)]);
        $this->assertEquals([0.0, 8400.00, 100.00], [(float) $updated->igst_percent, (float) $updated->total_payable, (float) $updated->usd_total]);
        $this->assertSame('Canada', $updated->client_country);
        $this->assertTrue($updated->isExport());
    }

    public function testBackToRupeesDropsTheDollarPrice(): void
    {
        $this->rateIs(88.0, '2026-11-14');
        $invoice = $this->service()->create($this->invoice(), [$this->item(1, 100.00)]);
        $edit = Invoice::findOne($invoice->id);
        $edit->priceCurrency = Invoice::CURRENCY_INR;
        $updated = $this->service()->update($edit, [$this->item(1, 9000.00)]);
        $this->assertFalse($updated->isUsdPriced());
        $this->assertNull($updated->usd_total);
        $this->assertEquals(9000.00, (float) $updated->total_payable);
    }

    public function testPayPalChargesExactlyTheDollarsOwedAndNoCouponApplies(): void
    {
        $this->rateIs(88.1234, '2026-11-14');
        $invoice = $this->service()->create($this->invoice(), [$this->item(1, 1500.00), $this->item(2, 249.99)]);

        $this->assertTrue(PayPalService::isLocked($invoice));
        $this->assertSame('1999.98', PayPalService::chargeFor((float) $invoice->total_payable, PayPalService::rateFor($invoice), $invoice));
        $this->assertSame('1500.00', PayPalService::chargeFor(132185.10, PayPalService::rateFor($invoice), $invoice), 'a part still owed');
        $this->assertSame('US$1,999.98', $invoice->money($invoice->total_payable));
        $this->assertSame('USD 1,999.98', $invoice->money($invoice->total_payable, true));
        $this->assertStringContainsString('US dollars', (string) (new InvoiceCouponService())->blockReason($invoice));
    }

    private function service(): ManualInvoiceService
    {
        return new ManualInvoiceService(exchangeRates: new ExchangeRateService($this->_http));
    }

    private function rateIs(float $rate, string $date): void
    {
        $this->_http->appendResponse(new Response(['content' => json_encode(['amount' => 1.0, 'base' => 'USD', 'date' => $date, 'rates' => ['INR' => $rate]]), 'headers' => ['http-code' => '200', 'content-type' => 'application/json']]));
    }

    private function invoice(): Invoice
    {
        return new Invoice(['user_id' => $this->_user->id, 'discount_amount' => 0.00, 'due_date' => date('Y-m-d', strtotime('+15 days')), 'priceCurrency' => Invoice::CURRENCY_USD]);
    }

    private function item(int $quantity, float $price): ManualInvoiceItemForm
    {
        $item = new ManualInvoiceItemForm();
        $item->category = 'Development';
        $item->description = 'Project work';
        $item->quantity = $quantity;
        $item->unit_price = $price;

        return $item;
    }
}
