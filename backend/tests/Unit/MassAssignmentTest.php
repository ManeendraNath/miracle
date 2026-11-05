<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use common\models\AuditRequest;
use common\models\Lead;
use common\models\SiteSetting;
use common\models\User;

/** What a posted form may and may not set. */
final class MassAssignmentTest extends Unit
{
    public function testAFormCannotSetACustomersCredentials(): void
    {
        $user = new User();
        $user->setAttributes(['username' => 'posted', 'password_hash' => 'x', 'auth_key' => 'x', 'password_reset_token' => 'x', 'verification_token' => 'x', 'created_at' => 1, 'updated_at' => 1, 'email' => ['array']]);

        $this->assertSame('posted', $user->username);
        $this->assertSame([null, null, null, null, null, null], [$user->password_hash, $user->auth_key, $user->password_reset_token, $user->verification_token, $user->created_at, $user->updated_at]);
        $this->assertSame('', $user->email, 'an array becomes an ordinary validation error');
    }

    public function testACustomerEmailMustBeAnEmail(): void
    {
        $user = new User(['email' => ' not-an-email ']);
        $this->assertFalse($user->validate(['email']));
        $this->assertSame('not-an-email', $user->email);
    }

    public function testThePublicAuditFormSetsOnlyItsOwnFields(): void
    {
        $request = new AuditRequest(['scenario' => AuditRequest::SCENARIO_PUBLIC]);
        $request->setAttributes(['name' => 'Posted', 'status' => AuditRequest::STATUS_CLOSED, 'user_ip' => '1.1.1.1', 'created_at' => 1]);

        $this->assertSame('Posted', $request->name);
        $this->assertSame([null, null, null], [$request->status, $request->user_ip, $request->created_at]);
    }

    public function testALeadStatusChangeSetsOnlyTheStatus(): void
    {
        $lead = new Lead(['scenario' => Lead::SCENARIO_STATUS]);
        $this->assertSame(['status'], $lead->safeAttributes());
    }

    public function testSiteSettingEmailsMustBeEmails(): void
    {
        $setting = new SiteSetting(['email_2' => 'nope']);
        $this->assertFalse($setting->validate(['email_2']));
    }
}
