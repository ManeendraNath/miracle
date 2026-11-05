<?php

declare(strict_types=1);

namespace frontend\tests\Functional;

use frontend\tests\Support\FunctionalTester;
use Yii;

final class HomeCest
{
    public function checkOpen(FunctionalTester $I): void
    {
        $I->amOnRoute('site/index');
        $I->see('Reliable hosting', 'h1');
        $I->amOnRoute('site/about');
        $I->see('What we do', 'h2');
    }
}
