<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

use InvalidArgumentException;

final class ReturnPolicy
{
    /** Order states where a return makes sense: something has shipped and the order is not cancelled/closed. */
    public const DEFAULT_STATES = ['processing', 'complete'];

    /**
     * @param string[] $allowedStates
     */
    public function __construct(
        public readonly int $windowDays,
        public readonly array $allowedStates = self::DEFAULT_STATES
    ) {
        if ($windowDays < 1) {
            throw new InvalidArgumentException('Return window must be at least 1 day.');
        }
    }
}
