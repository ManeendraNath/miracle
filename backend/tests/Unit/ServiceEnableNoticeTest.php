<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\models\BillingSettings;
use common\models\CronJob;
use common\models\CustomerBillingProfile;
use common\models\Hosting;
use common\models\InvoiceItem;
use common\models\User;
use common\services\billing\ServiceEnableNotice;
use DateTimeImmutable;

/** The question asked before a service is made Active again. */
final class ServiceEnableNoticeTest extends Unit
{
    public function testSaysNoPaymentAndWhenTheRenewalIsInvoiced(): void
    {
        $settings = BillingSettings::forType(InvoiceItem::TYPE_HOSTING);
        $days = max(0, (int) $settings->invoice_before_days);
        $today = new DateTimeImmutable('2026-11-12');
        $suffix = bin2hex(random_bytes(4));
        $user = new User(['username' => 'enable-notice-' . $suffix, 'email' => 'enable-' . $suffix . '@example.test', 'auth_key' => 'x', 'password_hash' => 'x', 'status' => User::getStatusActive(), 'created_at' => time(), 'updated_at' => time()]);
        $user->save(false);
        $hosting = new Hosting(['id' => 987654321, 'user_id' => $user->id, 'status' => Hosting::STATUS_SUSPENDED, 'renewal_base_amount' => 3500, 'current_expiry_date' => '2026-08-25']);

        try {
            $text = ServiceEnableNotice::confirm($hosting, $today);
            $this->assertStringContainsString('This hosting account is ' . Hosting::getStatuses()[Hosting::STATUS_SUSPENDED] . '. Make it Active? No payment is recorded.', $text);
            if (!(bool) $settings->is_active) {
                $this->assertStringContainsString('turned off', $text);

                return;
            }
            $this->assertStringContainsString('no billing profile', $text);

            $profile = new CustomerBillingProfile(['user_id' => $user->id, 'legal_name' => 'Notice Ltd', 'state' => 'Delhi', 'state_code' => '07', 'country' => 'India', 'is_gst_applicable' => 0, 'default_discount_amount' => 0, 'created_at' => time(), 'updated_at' => time()]);
            $profile->save(false);
            if (!CronJob::isEnabled('cron/run-billing')) {
                $this->assertStringContainsString('billing job is switched off', ServiceEnableNotice::confirm($hosting, $today));

                return;
            }
            $this->assertStringContainsString('will be invoiced by the next daily billing run', ServiceEnableNotice::confirm($hosting, $today));

            $hosting->current_expiry_date = $today->modify('+' . ($days + 30) . ' days')->format('Y-m-d');
            $this->assertStringContainsString('from ' . $today->modify('+30 days')->format('d M Y'), ServiceEnableNotice::confirm($hosting, $today));

            $hosting->renewal_base_amount = null;
            $this->assertStringContainsString('no renewal price', ServiceEnableNotice::confirm($hosting, $today));
        } finally {
            CustomerBillingProfile::deleteAll(['user_id' => $user->id]);
            $user->delete();
        }
    }
}
