<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use backend\models\searches\CouponSearch;
use Codeception\Test\Unit;

/** Empty grid filter boxes (posted as '') must not filter the coupon list. */
final class CouponSearchTest extends Unit
{
    public function testEmptyAmountFiltersAreIgnored(): void
    {
        /** @var \yii\db\ActiveQuery $query */
        $query = (new CouponSearch())->search(['CouponSearch' => ['amount' => '', 'minimum_order' => '', 'name' => '']])->query;
        $this->assertEmpty($query->where);

        /** @var \yii\db\ActiveQuery $query */
        $query = (new CouponSearch())->search(['CouponSearch' => ['amount' => '0']])->query;
        $this->assertSame(['amount' => '0'], $query->where);
    }
}
