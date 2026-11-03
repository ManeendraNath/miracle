<?php

declare(strict_types=1);

namespace frontend\tests\Unit\Services;

use Codeception\Test\Unit;
use common\fixtures\UserFixture;
use common\models\CustomerPreference;
use common\models\SupportTicket;
use common\models\User;
use common\services\support\SupportTicketMailer;
use frontend\tests\Support\UnitTester;

/** "Support request updates" off: the customer gets no ticket update emails. */
final class SupportTicketMailerTest extends Unit
{
    protected UnitTester $tester;

    public function _before(): void
    {
        $this->tester->haveFixtures(['user' => ['class' => UserFixture::class, 'dataFile' => codecept_data_dir() . 'user.php']]);
    }

    public function testCustomerUpdatesFollowTheSupportPreference(): void
    {
        $user = User::find()->orderBy(['id' => SORT_ASC])->one();
        $ticket = new SupportTicket();
        $ticket->setAttributes(['ticket_number' => 'SUP-TEST-' . bin2hex(random_bytes(4)), 'user_id' => $user->id, 'subject' => 'Help', 'message' => 'Please help me', 'priority' => SupportTicket::PRIORITY_HIGH, 'status' => SupportTicket::STATUS_OPEN, 'created_at' => time(), 'updated_at' => time()], false);
        $this->assertTrue($ticket->save(false));

        SupportTicketMailer::adminReply($ticket);
        $this->tester->seeEmailIsSent(1);

        $preference = CustomerPreference::forUser((int) $user->id);
        $preference->support_notifications = 0;
        $preference->updated_at = time();
        $this->assertTrue($preference->save(false));

        SupportTicketMailer::adminReply($ticket);
        $this->tester->seeEmailIsSent(1); // still only the first one

        $ticket->delete();
        $preference->delete();
    }
}
