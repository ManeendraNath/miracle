<?php

declare(strict_types=1);

namespace backend\tests\Unit;

use Codeception\Test\Unit;
use common\components\Totp;

final class TotpTest extends Unit
{
    /** base32 of the RFC 6238 SHA1 test key "12345678901234567890". */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * RFC 6238 appendix B (SHA1). The RFC prints 8 digits; a 6-digit code is the
     * same truncated value mod 10^6, i.e. the last six digits.
     */
    public function testRfc6238Sha1Vectors(): void
    {
        $vectors = [
            59 => '94287082',
            1111111109 => '07081804',
            1111111111 => '14050471',
            1234567890 => '89005924',
            2000000000 => '69279037',
            20000000000 => '65353130',
        ];
        foreach ($vectors as $time => $expected) {
            $this->assertSame(substr($expected, -6), Totp::code(self::RFC_SECRET, intdiv($time, 30)), "time $time");
            $this->assertSame(intdiv($time, 30), Totp::verify(self::RFC_SECRET, substr($expected, -6), null, $time));
        }
    }

    public function testAcceptsOneStepEitherSideOnly(): void
    {
        $time = 1234567890;
        $step = intdiv($time, 30);
        $this->assertSame($step - 1, Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step - 1), null, $time));
        $this->assertSame($step + 1, Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step + 1), null, $time));
        $this->assertNull(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step - 2), null, $time));
        $this->assertNull(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step + 2), null, $time));
        $this->assertNull(Totp::verify(self::RFC_SECRET, 'abcdef', null, $time));
        $this->assertNull(Totp::verify(self::RFC_SECRET, '', null, $time));
    }

    public function testRejectsReplayOfUsedStep(): void
    {
        $time = 1234567890;
        $step = intdiv($time, 30);
        $code = Totp::code(self::RFC_SECRET, $step);
        $this->assertSame($step, Totp::verify(self::RFC_SECRET, $code, $step - 1, $time));
        $this->assertNull(Totp::verify(self::RFC_SECRET, $code, $step, $time));
        // An older code is refused once a newer step was used.
        $this->assertNull(Totp::verify(self::RFC_SECRET, Totp::code(self::RFC_SECRET, $step - 1), $step, $time));
    }

    public function testGeneratedSecretAndUri(): void
    {
        $secret = Totp::generateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertSame(6, strlen(Totp::code($secret, 1)));
        $this->assertSame(
            'otpauth://totp/Miracle%20Admin:jo%40example.com?secret=ABC&issuer=Miracle%20Admin&algorithm=SHA1&digits=6&period=30',
            Totp::uri('ABC', 'jo@example.com', 'Miracle Admin'),
        );
    }
}
