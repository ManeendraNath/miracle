<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use backend\controllers\BaseController;
use Codeception\Test\Unit;
use Yii;

/** In a portal-only copy the admin's website sections are closed, billing stays. */
final class PortalOnlyTest extends Unit
{
    protected function _after(): void
    {
        Yii::$app->params['portalOnly'] = false;
        parent::_after();
    }

    public function testWebsiteSectionsCloseAndBillingStays(): void
    {
        $owner = (object) ['role' => 'Superadmin'];
        $this->assertTrue(BaseController::canAccess($owner, 'blog-post'));
        Yii::$app->params['portalOnly'] = true;
        foreach (['blog-post', 'product', 'product-order', 'seo-page', 'lead'] as $section) {
            $this->assertFalse(BaseController::canAccess($owner, $section), $section);
        }
        foreach (['invoice', 'hosting', 'domains', 'maintenance', 'site-setting'] as $section) {
            $this->assertTrue(BaseController::canAccess($owner, $section), $section);
        }
    }
}
