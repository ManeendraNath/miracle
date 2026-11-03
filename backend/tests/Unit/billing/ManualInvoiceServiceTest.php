<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\CustomerBillingProfile;
use common\models\Invoice;
use common\models\InvoiceItem;
use common\models\User;
use common\models\forms\ManualInvoiceItemForm;
use common\services\billing\ManualInvoiceService;
use Yii;
use yii\db\Transaction as DbTransaction;

final class ManualInvoiceServiceTest extends Unit
{

    private ?DbTransaction $dbTransaction = null;
    private User $user;
    private CustomerBillingProfile $profile;

    protected function _before(): void
    {
        parent::_before();

        // Assertions assume standard GST, independent of the live params (currently 0%).
        Yii::$app->params['billing']['gst'] = ['cgst' => 9.00, 'sgst' => 9.00, 'igst' => 18.00];

        $this->dbTransaction = Yii::$app->db->beginTransaction();

        $this->createClient();
    }

    protected function _after(): void
    {
        if (
                $this->dbTransaction !== null && $this->dbTransaction->isActive
        ) {
            $this->dbTransaction->rollBack();
        }

        parent::_after();
    }

    public function testCreatesManualInvoiceWithMultipleItems(): void
    {
        $invoice = $this->newInvoice();

        $items = [
            $this->item(
                    'Development',
                    'Custom website development',
                    1,
                    10000.00
            ),
            $this->item(
                    'Support',
                    'Technical support package',
                    2,
                    2500.00
            ),
        ];

        $service = new ManualInvoiceService();

        $created = $service->create(
                $invoice,
                $items
        );

        $this->assertFalse(
                $created->isNewRecord
        );

        $this->assertMatchesRegularExpression(
                '/^MWT' . \common\services\billing\InvoiceNumberService::financialYear((int) $created->created_at) . '-\d{5}$/',
                $created->invoice_number
        );

        $this->assertSame(
                Invoice::STATUS_UNPAID,
                $created->status
        );

        /*
         * 10000 + (2 * 2500)
         */
        $this->assertEquals(
                15000.00,
                (float) $created->subtotal_amount
        );

        /*
         * Delhi client:
         *
         * CGST = 9
         * SGST = 9
         * IGST = 0
         */
        $this->assertEquals(
                9.00,
                (float) $created->cgst_percent
        );

        $this->assertEquals(
                9.00,
                (float) $created->sgst_percent
        );

        $this->assertEquals(
                0.00,
                (float) $created->igst_percent
        );

        /*
         * Subtotal: 15000
         * Discount: 1000
         * Taxable: 14000
         * GST 18%: 2520
         * Total: 16520
         */
        $this->assertEquals(
                16520.00,
                (float) $created->total_payable
        );

        $savedItems = InvoiceItem::find()
                ->where([
                    'invoice_id' => $created->id,
                ])
                ->orderBy([
                    'id' => SORT_ASC,
                ])
                ->all();

        $this->assertCount(
                2,
                $savedItems
        );

        $this->assertSame(
                InvoiceItem::TYPE_CUSTOM,
                (int) $savedItems[0]->item_type
        );

        $this->assertEquals(
                10000.00,
                (float) $savedItems[0]->total_price
        );

        $this->assertEquals(
                5000.00,
                (float) $savedItems[1]->total_price
        );
    }

    public function testUpdateReplacesManualItems(): void
    {
        $service = new ManualInvoiceService();

        $invoice = $service->create(
                $this->newInvoice(),
                [
                    $this->item(
                            'Old Service',
                            'Original line',
                            1,
                            5000.00
                    ),
                ]
        );

        $invoice->discount_amount = 500.00;

        $updated = $service->update(
                $invoice,
                [
                    $this->item(
                            'Development',
                            'Updated development',
                            1,
                            8000.00
                    ),
                    $this->item(
                            'Support',
                            'Updated support',
                            1,
                            2000.00
                    ),
                ]
        );

        $updated->refresh();

        $this->assertEquals(
                10000.00,
                (float) $updated->subtotal_amount
        );

        /*
         * 10000 - 500 = 9500
         * 9500 + 18% = 11210
         */
        $this->assertEquals(
                11210.00,
                (float) $updated->total_payable
        );

        $items = InvoiceItem::find()
                ->where([
                    'invoice_id' =>
                    $updated->id,
                ])
                ->orderBy([
                    'id' => SORT_ASC,
                ])
                ->all();

        $this->assertCount(
                2,
                $items
        );

        $this->assertSame(
                'Development',
                $items[0]->category
        );

        $this->assertSame(
                'Support',
                $items[1]->category
        );
    }

    public function testRejectsInvoiceWithoutItems(): void
    {
        $this->expectException(
                \DomainException::class
        );

        (new ManualInvoiceService())
                ->create(
                        $this->newInvoice(),
                        []
                );
    }

    public function testRejectsInvalidQuantity(): void
    {
        $item = new ManualInvoiceItemForm();

        $item->category = 'Development';

        $item->description = 'Invalid quantity';

        $item->quantity = 0;

        $item->unit_price = 1000.00;

        $this->expectException(
                \DomainException::class
        );

        (new ManualInvoiceService())
                ->create(
                        $this->newInvoice(),
                        [$item]
                );
    }

    public function testRejectsDiscountGreaterThanSubtotal(): void
    {
        $invoice = $this->newInvoice();

        $invoice->discount_amount = 2000.00;

        $this->expectException(
                \DomainException::class
        );

        (new ManualInvoiceService())
                ->create(
                        $invoice,
                        [
                            $this->item(
                                    'Service',
                                    'Small service',
                                    1,
                                    1000.00
                            ),
                        ]
                );
    }

    public function testCannotUpdatePartiallyPaidInvoice(): void
    {
        $service = new ManualInvoiceService();

        $invoice = $service->create(
                $this->newInvoice(),
                [
                    $this->item(
                            'Service',
                            'Original service',
                            1,
                            5000.00
                    ),
                ]
        );

        $invoice->status = Invoice::STATUS_PARTIALLY_PAID;

        $invoice->save(
                false,
                [
                    'status',
                    'updated_at',
                ]
        );

        $this->expectException(
                \DomainException::class
        );

        $service->update(
                $invoice,
                [
                    $this->item(
                            'Changed',
                            'Must not save',
                            1,
                            1000.00
                    ),
                ]
        );
    }

    private function newInvoice(): Invoice
    {
        $invoice = new Invoice();

        $invoice->user_id = $this->user->id;

        $invoice->discount_amount = 1000.00;

        $invoice->due_date = date(
                'Y-m-d',
                strtotime('+15 days')
        );

        return $invoice;
    }

    private function item(
            string $category,
            string $description,
            int $quantity,
            float $unitPrice
    ): ManualInvoiceItemForm
    {
        $item = new ManualInvoiceItemForm();

        $item->category = $category;

        $item->description = $description;

        $item->quantity = $quantity;

        $item->unit_price = $unitPrice;

        return $item;
    }

    private function createClient(): void
    {
        $suffix = bin2hex(
                random_bytes(4)
        );

        $this->user = new User();

        $this->user->username = 'billing-test-' . $suffix;

        $this->user->email = 'billing-' . $suffix
                . '@example.test';

        // User hashes `password` and generates auth_key itself on save.
        $this->user->password = 'BillingTestPassword';

        $this->user->status = User::getStatusActive();

        $this->user->created_at = time();
        $this->user->updated_at = time();

        $this->assertTrue(
                $this->user->save(),
                json_encode(
                        $this->user->errors
                )
        );

        $this->profile = new CustomerBillingProfile();

        $this->profile->user_id = $this->user->id;

        $this->profile->legal_name = 'Billing Test Customer';

        $this->profile->address_line_1 = 'Test Address';

        $this->profile->city = 'Delhi';

        $this->profile->state = 'Delhi';

        $this->profile->state_code = '07';

        $this->profile->is_gst_applicable = 1;

        $this->profile->postal_code = '110001';

        $this->profile->created_at = time();

        $this->profile->updated_at = time();

        $this->assertTrue(
                $this->profile->save(),
                json_encode(
                        $this->profile->errors
                )
        );
    }

    public function testInterstateCustomerUsesIgst(): void
    {
        $this->profile->state = 'Maharashtra';

        $this->profile->state_code = '27';

        $this->assertTrue(
                $this->profile->save()
        );

        $invoice = $this->newInvoice();

        $invoice->discount_amount = 0.00;

        $created = (new ManualInvoiceService())
                ->create(
                        $invoice,
                        [
                            $this->item(
                                    'Development',
                                    'Interstate service',
                                    1,
                                    10000.00
                            ),
                        ]
                );

        $this->assertEquals(
                0.00,
                (float) $created->cgst_percent
        );

        $this->assertEquals(
                0.00,
                (float) $created->sgst_percent
        );

        $this->assertEquals(
                18.00,
                (float) $created->igst_percent
        );

        $this->assertEquals(
                11800.00,
                (float) $created->total_payable
        );
    }
    
    public function testInvalidItemRollsBackWholeInvoice(): void
{
    $beforeInvoices =
        (int) Invoice::find()->count();

    $beforeItems =
        (int) InvoiceItem::find()->count();

    $valid =
        $this->item(
            'Valid',
            'Valid item',
            1,
            5000.00
        );

    $invalid =
        $this->item(
            '',
            '',
            1,
            1000.00
        );

    try {

        (new ManualInvoiceService())
            ->create(
                $this->newInvoice(),
                [
                    $valid,
                    $invalid,
                ]
            );

        $this->fail(
            'Expected DomainException.'
        );

    } catch (\DomainException $e) {
        /*
         * Expected.
         */
    }

    $this->assertSame(
        $beforeInvoices,
        (int) Invoice::find()->count()
    );

    $this->assertSame(
        $beforeItems,
        (int) InvoiceItem::find()->count()
    );
}

    public function testCouponLimitedToServiceTypesDiscountsOnlyThoseLines(): void
    {
        $this->coupon('HOST10', ['applies_to' => [(string) InvoiceItem::TYPE_HOSTING, (string) InvoiceItem::TYPE_DOMAIN]]);
        $invoice = $this->newInvoice();
        $invoice->coupon_code = 'host10';
        $hosting = $this->item('Hosting', 'Business hosting', 1, 1000.00);
        $hosting->item_type = InvoiceItem::TYPE_HOSTING;
        $created = (new ManualInvoiceService())->create($invoice, [$hosting, $this->item('Design', 'Logo design', 1, 500.00)]);

        // 10% of the hosting line only, not of the 1500 subtotal.
        $this->assertEquals(100.00, (float) $created->discount_amount);
        $this->assertEquals(1500.00, (float) $created->subtotal_amount);
        $this->assertEquals(
            [InvoiceItem::TYPE_HOSTING, InvoiceItem::TYPE_CUSTOM],
            array_map('intval', InvoiceItem::find()->select('item_type')->where(['invoice_id' => $created->id])->orderBy('id')->column())
        );
    }

    public function testCouponLimitedToOtherServiceTypeIsRejected(): void
    {
        $this->coupon('DOMAINONLY', ['applies_to' => [(string) InvoiceItem::TYPE_DOMAIN]]);
        $invoice = $this->newInvoice();
        $invoice->coupon_code = 'DOMAINONLY';
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Coupon cannot be used for this service.');
        (new ManualInvoiceService())->create($invoice, [$this->item('Design', 'Logo design', 1, 500.00)]);
    }

    public function testCouponAssignedToCustomerRejectsOthersAndSurvivesPlainSave(): void
    {
        $coupon = $this->coupon('VIP', ['assigned_user_ids' => [(string) $this->user->id]]);
        $coupons = new \common\services\billing\CouponService();
        $this->assertEquals(10.00, $coupons->calculate('VIP', (int) $this->user->id, 100.00));

        // A save without the form fields (e.g. status toggle) keeps the restrictions.
        $reloaded = \common\models\Coupon::findOne($coupon->id);
        $this->assertTrue($reloaded->save());
        $reloaded->loadRestrictions();
        $this->assertSame([(string) $this->user->id], $reloaded->assigned_user_ids);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('not assigned to your account');
        $coupons->calculate('VIP', (int) $this->user->id + 100000, 100.00);
    }

    private function coupon(string $code, array $restrictions): \common\models\Coupon
    {
        $coupon = new \common\models\Coupon();
        $coupon->setAttributes(['name' => $code, 'code' => $code, 'type' => \common\models\Coupon::TYPE_P, 'amount' => 10, 'is_active' => \common\models\Coupon::IS_ACTIVE_Y, 'uses_per_coupon' => 0] + $restrictions);
        $this->assertTrue($coupon->save(), json_encode($coupon->errors));
        return $coupon;
    }
}
