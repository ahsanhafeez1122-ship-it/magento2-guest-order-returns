<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Lookup;

final class LookupInput
{
    public function __construct(
        public readonly string $incrementId,
        public readonly string $email,
        public readonly string $postcode
    ) {
    }
}
