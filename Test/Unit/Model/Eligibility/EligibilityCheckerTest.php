<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Test\Unit\Model\Eligibility;

use AhsanHafeez\GuestReturns\Model\Eligibility\EligibilityChecker;
use AhsanHafeez\GuestReturns\Model\Eligibility\EligibilityResult;
use AhsanHafeez\GuestReturns\Model\Eligibility\ItemSnapshot;
use AhsanHafeez\GuestReturns\Model\Eligibility\OrderSnapshot;
use AhsanHafeez\GuestReturns\Model\Eligibility\ReturnPolicy;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class EligibilityCheckerTest extends TestCase
{
    private EligibilityChecker $checker;
    private ReturnPolicy $policy;

    protected function setUp(): void
    {
        $this->checker = new EligibilityChecker();
        $this->policy = new ReturnPolicy(30);
    }

    public function testShippedOrderInsideWindowIsEligible(): void
    {
        $order = $this->order('complete', '2026-09-01 10:00:00', [
            new ItemSnapshot(11, 'SOFA-GREY', 'Grey Sofa', 1),
            new ItemSnapshot(12, 'CUSHION', 'Cushion', 4),
        ]);

        $result = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-09-20 10:00:00'));

        self::assertTrue($result->eligible);
        self::assertSame([11 => 1.0, 12 => 4.0], $result->returnableQty);
        self::assertSame('2026-10-01', $result->deadline->format('Y-m-d'));
    }

    public function testWindowIsCountedFromLatestShipment(): void
    {
        $order = $this->order('complete', '2026-09-01 10:00:00', [new ItemSnapshot(11, 'A', 'A', 1)]);

        $lastDay = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-10-01 10:00:00'));
        $dayAfter = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-10-01 10:00:01'));

        self::assertTrue($lastDay->eligible);
        self::assertFalse($dayAfter->eligible);
        self::assertSame(EligibilityResult::REASON_EXPIRED, $dayAfter->reason);
    }

    public function testUnshippedOrderIsNotEligible(): void
    {
        $order = $this->order('processing', null, [new ItemSnapshot(11, 'A', 'A', 0)]);

        $result = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-09-20'));

        self::assertSame(EligibilityResult::REASON_NOT_SHIPPED, $result->reason);
    }

    public function testCancelledOrClosedOrderIsNotEligible(): void
    {
        foreach (['canceled', 'closed', 'holded'] as $state) {
            $order = $this->order($state, '2026-09-01 10:00:00', [new ItemSnapshot(11, 'A', 'A', 1)]);
            $result = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-09-02'));
            self::assertSame(EligibilityResult::REASON_STATE, $result->reason, $state);
        }
    }

    public function testRefundedAndAlreadyRequestedQtyIsSubtracted(): void
    {
        $order = $this->order('complete', '2026-09-01 10:00:00', [
            new ItemSnapshot(11, 'A', 'A', qtyShipped: 5, qtyRefunded: 1, qtyAlreadyRequested: 2),
            new ItemSnapshot(12, 'B', 'B', qtyShipped: 2, qtyRefunded: 2),
        ]);

        $result = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-09-02'));

        self::assertSame([11 => 2.0], $result->returnableQty);
    }

    public function testNothingLeftToReturn(): void
    {
        $order = $this->order('complete', '2026-09-01 10:00:00', [
            new ItemSnapshot(11, 'A', 'A', qtyShipped: 1, qtyAlreadyRequested: 1),
            new ItemSnapshot(12, 'GIFT-CARD', 'Gift Card', qtyShipped: 1, isVirtual: true),
        ]);

        $result = $this->checker->check($order, $this->policy, new DateTimeImmutable('2026-09-02'));

        self::assertSame(EligibilityResult::REASON_NOTHING_LEFT, $result->reason);
        self::assertNotSame('', $result->customerMessage());
    }

    public function testPolicyRejectsZeroDayWindow(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ReturnPolicy(0);
    }

    /**
     * @param ItemSnapshot[] $items
     */
    private function order(string $state, ?string $shippedAt, array $items): OrderSnapshot
    {
        return new OrderSnapshot('000001234', $state, $shippedAt ? new DateTimeImmutable($shippedAt) : null, $items);
    }
}
