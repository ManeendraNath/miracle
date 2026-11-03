<?php

declare(strict_types=1);

namespace backend\tests\unit\billing;

use Codeception\Test\Unit;
use common\services\billing\RenewalPeriodCalculator;

final class RenewalPeriodCalculatorTest extends Unit
{

    /**
     * @dataProvider periods
     */
    public function testCalculate(string $expiry, int $months, string $start, string $end): void
    {
        $this->assertSame(
                ['start' => $start, 'end' => $end],
                (new RenewalPeriodCalculator())->calculate($expiry, $months)
        );
    }

    public static function periods(): array
    {
        return [
            'mid-month' => ['2026-03-15', 1, '2026-03-16', '2026-04-15'],
            'yearly' => ['2026-03-15', 12, '2026-03-16', '2027-03-15'],
            'day 30 into Feb clamps' => ['2026-01-30', 1, '2026-01-31', '2026-02-28'],
            'day 29 into Feb clamps' => ['2026-01-29', 1, '2026-01-30', '2026-02-28'],
            'leap Feb' => ['2028-01-30', 1, '2028-01-31', '2028-02-29'],
            'month end stays month end' => ['2026-02-28', 1, '2026-03-01', '2026-03-31'],
            'Jan 31 to Feb end' => ['2026-01-31', 1, '2026-02-01', '2026-02-28'],
            'leap day yearly' => ['2028-02-29', 12, '2028-03-01', '2029-02-28'],
            'quarterly across year' => ['2026-11-30', 3, '2026-12-01', '2027-02-28'],
            'Feb 28 yearly into leap year stays 28' => ['2027-02-28', 12, '2027-03-01', '2028-02-28'],
            'Apr 30 yearly stays 30' => ['2026-04-30', 12, '2026-05-01', '2027-04-30'],
        ];
    }

    public function testMonthlyCycleReturnsToItsAnchorDayAfterAShortMonth(): void
    {
        $calculator = new RenewalPeriodCalculator();
        $expiry = '2026-01-30';
        $ends = [];
        for ($i = 0; $i < 4; $i++) {
            $expiry = $calculator->calculate($expiry, 1, 30)['end'];
            $ends[] = $expiry;
        }
        // Without the anchor this drifted to 31 Mar, 30 Apr, 31 May.
        $this->assertSame(['2026-02-28', '2026-03-30', '2026-04-30', '2026-05-30'], $ends);
    }

    public function testMonthEndAnchorStaysAtMonthEnd(): void
    {
        $this->assertSame('2026-03-31', (new RenewalPeriodCalculator())->calculate('2026-02-28', 1, 31)['end']);
        $this->assertSame('2026-04-30', (new RenewalPeriodCalculator())->calculate('2026-03-31', 1, 31)['end']);
    }

    public function testYearlyCyclesIgnoreTheAnchor(): void
    {
        $this->assertSame('2028-02-28', (new RenewalPeriodCalculator())->calculate('2027-02-28', 12, 31)['end']);
    }

    public function testAnchorDayForAStaffEnteredDate(): void
    {
        $this->assertSame(30, RenewalPeriodCalculator::anchorDayFor('2026-01-30'));
        $this->assertSame(31, RenewalPeriodCalculator::anchorDayFor('2026-02-28'));
        $this->assertSame(31, RenewalPeriodCalculator::anchorDayFor('2026-04-30'));
        $this->assertSame(15, RenewalPeriodCalculator::anchorDayFor('2026-04-15'));
        $this->assertNull(RenewalPeriodCalculator::anchorDayFor('not-a-date'));
    }
}
