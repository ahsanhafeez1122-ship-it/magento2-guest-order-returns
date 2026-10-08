<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

use DateTimeImmutable;

final class OrderSnapshot
{
    /**
     * @param ItemSnapshot[] $items
     */
    public function __construct(
        public readonly string $incrementId,
        public readonly string $state,
        public readonly ?DateTimeImmutable $lastShippedAt,
        public readonly array $items
    ) {
    }
}
