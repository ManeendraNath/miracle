<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\AdminNotification;
use common\models\CustomerBillingProfile;
use common\models\User;
use common\services\customer\CustomerBillingDetailsService;
use Yii;
use yii\db\Transaction as DbTransaction;

final class CustomerBillingDetailsTest extends Unit
{
    private ?DbTransaction $_dbTransaction = null;
    private User $_user;

    protected function _before(): void
    {
        parent::_before();
        $this->_dbTransaction = Yii::$app->db->beginTransaction();
        $suffix = bin2hex(random_bytes(4));
        $this->_user = new User();
        $this->_user->username = 'billing-details-' . $suffix;
        $this->_user->email = 'billing-details-' . $suffix . '@example.test';
        $this->_user->password = 'BillingTestPassword';
        $this->_user->status = User::getStatusActive();
        $this->_user->created_at = time();
        $this->_user->updated_at = time();
        $this->assertTrue($this->_user->save(), json_encode($this->_user->errors));
    }

    protected function _after(): void
    {
        if ($this->_dbTransaction !== null && $this->_dbTransaction->isActive) {
            $this->_dbTransaction->rollBack();
        }
        parent::_after();
    }

    public function testValidationRules(): void
    {
        $valid = $this->validated([]);
        $this->assertSame([], $valid->errors);
        $this->assertSame('27', $valid->state_code, 'state_code is derived from the state');
        $this->assertSame('27ABCDE1234F1Z5', $valid->gstin, 'GSTIN is trimmed and uppercased');
        $this->assertSame('ABCDE1234F', $valid->pan);

        $this->assertArrayHasKey('gstin', $this->validated(['state' => 'Delhi'])->errors, 'GSTIN prefix must match the state');
        $this->assertArrayHasKey('gstin', $this->validated(['gstin' => '27ABCDE1234F1Z'])->errors, '15 chars');
        $this->assertArrayHasKey('pan', $this->validated(['pan' => 'ABCDE1234G'])->errors, 'PAN must equal GSTIN chars 3-12');
        $this->assertArrayHasKey('pan', $this->validated(['pan' => 'ABCD12345F', 'gstin' => ''])->errors, 'PAN format');
        $this->assertArrayHasKey('postal_code', $this->validated(['postal_code' => '4000'])->errors);
        $this->assertArrayHasKey('state', $this->validated(['state' => 'Bombay'])->errors);
        $this->assertSame([], $this->validated(['gstin' => '', 'pan' => ''])->errors, 'GSTIN and PAN are optional');
        $this->assertSame([], $this->validated(['gstin' => ''])->errors, 'PAN alone is fine');

        // Admin (default scenario) gets the same format rules.
        $admin = new CustomerBillingProfile(['user_id' => $this->_user->id, 'legal_name' => 'X', 'state' => 'Delhi', 'gstin' => '27ABCDE1234F1Z5']);
        $this->assertFalse($admin->validate());
        $this->assertArrayHasKey('gstin', $admin->errors);
        $this->assertSame('07', $admin->state_code);
    }

    public function testCustomerCreatesAndUpdatesWithoutTouchingPricingFields(): void
    {
        $alerts = static fn (): int => (int) AdminNotification::find()->where(['type' => 'billing'])->count();
        $before = $alerts();

        $profile = CustomerBillingDetailsService::forUser($this->_user);
        $this->assertTrue($profile->isNewRecord);
        $post = ['CustomerBillingProfile' => $this->input([
            'is_gst_applicable' => 1,
            'default_discount_amount' => 999,
            'user_id' => 1,
        ])];
        $this->assertTrue(CustomerBillingDetailsService::save($profile, $post), json_encode($profile->errors));

        $saved = CustomerBillingProfile::findOne(['user_id' => $this->_user->id]);
        $this->assertNotNull($saved);
        $this->assertSame(0, (int) $saved->is_gst_applicable);
        $this->assertEquals(0, $saved->default_discount_amount);
        $this->assertSame('27', $saved->state_code);
        $this->assertSame($before + 1, $alerts(), 'admins alerted on create');

        $profile = CustomerBillingDetailsService::forUser($this->_user);
        $this->assertFalse($profile->isNewRecord);
        $this->assertTrue(CustomerBillingDetailsService::save($profile, ['CustomerBillingProfile' => $this->input([])]));
        $this->assertSame($before + 1, $alerts(), 'no alert when nothing changed');

        $this->assertTrue(CustomerBillingDetailsService::save($profile, ['CustomerBillingProfile' => $this->input(['city' => 'Pune'])]));
        $this->assertSame('Pune', CustomerBillingProfile::findOne($saved->id)->city);
        $this->assertSame($before + 2, $alerts(), 'admins alerted on change');

        $this->assertFalse(CustomerBillingDetailsService::save($profile, ['CustomerBillingProfile' => $this->input(['state' => 'Delhi'])]));
        $this->assertSame('27', CustomerBillingProfile::findOne($saved->id)->state_code, 'invalid input is not saved');
    }

    private function input(array $override): array
    {
        return array_merge([
            'legal_name' => 'Acme Pvt Ltd',
            'address_line_1' => '1 Main Road',
            'address_line_2' => '',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'gstin' => ' 27abcde1234f1z5 ',
            'pan' => 'abcde1234f',
        ], $override);
    }

    private function validated(array $override): CustomerBillingProfile
    {
        $profile = CustomerBillingDetailsService::forUser($this->_user);
        $profile->setAttributes($this->input($override));
        $profile->validate();

        return $profile;
    }
}
