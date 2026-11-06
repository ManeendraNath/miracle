<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\models\SupportTicket;

final class SupportPostLimitTest extends Unit
{
    public function testLimitAppliesPerCustomer(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $this->assertFalse(SupportTicket::overPostLimit(990001));
        }
        $this->assertTrue(SupportTicket::overPostLimit(990001));
        $this->assertFalse(SupportTicket::overPostLimit(990002), 'another customer is not affected');
    }
}
