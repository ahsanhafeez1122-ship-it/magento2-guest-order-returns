<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Test\Unit\Model\Lookup;

use AhsanHafeez\GuestReturns\Model\Lookup\AttemptLimiter;
use PHPUnit\Framework\TestCase;

final class AttemptLimiterTest extends TestCase
{
    public function testBlocksAfterMaxFailuresInsideWindow(): void
    {
        $limiter = new AttemptLimiter(3, 900);
        $failed = [];
        foreach ([1000, 1100, 1200] as $t) {
            self::assertFalse($limiter->isBlocked($failed, $t));
            $failed = $limiter->registerFailure($failed, $t);
        }

        self::assertTrue($limiter->isBlocked($failed, 1300));
        self::assertSame(600, $limiter->retryAfter($failed, 1300));
    }

    public function testOldFailuresExpire(): void
    {
        $limiter = new AttemptLimiter(3, 900);
        $failed = [1000, 1100, 1200];

        self::assertFalse($limiter->isBlocked($failed, 1901));
        self::assertSame([1100, 1200], $limiter->prune($failed, 1901));
        self::assertSame(0, $limiter->retryAfter($failed, 1901));
    }
}
