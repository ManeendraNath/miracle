<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use backend\controllers\BaseController;
use Codeception\Test\Unit;

/** Pins who can open what (BaseController::MIN_ROLE). */
final class RoleAccessTest extends Unit
{
    public function testRoleMatrix(): void
    {
        $routes = [
            // route => [Manager, Admin, Superadmin]
            'dashboard/index' => [true, true, true],
            'support-ticket/view' => [true, true, true],
            'user/index' => [true, true, true],
            'admin/account' => [true, true, true],
            'invoice/index' => [false, true, true],
            'transaction/approve' => [false, true, true],
            'coupon/create' => [false, true, true],
            'admin/cron-log' => [false, true, true],
            'admin/index' => [false, false, true],
            'admin/update' => [false, false, true],
            'site-setting/index' => [false, false, true],
        ];
        foreach ($routes as $route => $expected) {
            [$controller, $action] = explode('/', $route);
            foreach (['Manager', 'Admin', 'Superadmin'] as $i => $role) {
                $this->assertSame($expected[$i], BaseController::canAccess((object) ['role' => $role], $controller, $action), "{$role} {$route}");
            }
            $this->assertFalse(BaseController::canAccess((object) ['role' => 'Customer'], $controller, $action), "unknown role {$route}");
            $this->assertFalse(BaseController::canAccess(null, $controller, $action), "guest {$route}");
        }
    }
}
