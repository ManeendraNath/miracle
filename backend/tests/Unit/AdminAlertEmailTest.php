<?php

declare(strict_types=1);

namespace backend\tests\unit;

use Codeception\Test\Unit;
use Yii;

/** Staff emails point at the admin panel, never at the customer portal. */
final class AdminAlertEmailTest extends Unit
{
    public function testAlertLinksToItsAdminPageAndAdminPanel(): void
    {
        $mailer = Yii::$app->mailer;
        $this->assertInstanceOf(\yii\mail\BaseMailer::class, $mailer);
        $html = $mailer->render('admin-alert-html', ['title' => 'Check', 'message' => 'Body', 'route' => '/hosting/index'], 'layouts/html');
        $admin = Yii::$app->params['backendBaseUrl'];
        $this->assertStringContainsString('href="' . $admin . '/hosting/index"', $html);
        $this->assertStringContainsString('Open the admin panel', $html);
        $this->assertStringNotContainsString('Access Customer Portal', $html);
    }
}
