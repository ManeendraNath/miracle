<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\components\View;

final class CspNonceTest extends Unit
{
    public function testOnlyScriptsTheAppRegistersGetTheNonce(): void
    {
        $view = new View();
        $view->registerJs('var registered = 1;', View::POS_END);
        $view->registerJsFile('https://checkout.razorpay.com/v1/checkout.js');
        $html = $view->renderAjax('@backend/tests/Support/_csp-page.php');
        $nonce = $view->getNonce();

        $this->assertSame(2, substr_count($html, 'nonce="' . $nonce . '"'), 'the inline block and the file');
        $this->assertStringContainsString('<script>var injected = 1;</script>', $html, 'a script in page content gets no nonce, so the policy blocks it');
        $this->assertSame($nonce, $view->getNonce(), 'one value per request');
    }
}
