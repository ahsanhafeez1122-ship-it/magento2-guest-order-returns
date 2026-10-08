<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Lookup;

/**
 * Sliding-window limit on failed lookups, so order numbers can't be guessed by brute force.
 * Works on a plain list of unix timestamps; storage is the caller's job.
 */
final class AttemptLimiter
{
    public function __construct(
        private readonly int $maxAttempts,
        private readonly int $windowSeconds
    ) {
    }

    /**
     * @param int[] $failedAt
     * @return int[]
     */
    public function prune(array $failedAt, int $now): array
    {
        $cutoff = $now - $this->windowSeconds;

        return array_values(array_filter($failedAt, static fn (int $t): bool => $t > $cutoff));
    }

    /**
     * @param int[] $failedAt
     */
    public function isBlocked(array $failedAt, int $now): bool
    {
        return count($this->prune($failedAt, $now)) >= $this->maxAttempts;
    }

    /**
     * @param int[] $failedAt
     * @return int[]
     */
    public function registerFailure(array $failedAt, int $now): array
    {
        $failedAt = $this->prune($failedAt, $now);
        $failedAt[] = $now;

        return $failedAt;
    }

    /**
     * Seconds until the oldest failure in the window expires; 0 when not blocked.
     *
     * @param int[] $failedAt
     */
    public function retryAfter(array $failedAt, int $now): int
    {
        $failedAt = $this->prune($failedAt, $now);
        if (count($failedAt) < $this->maxAttempts) {
            return 0;
        }

        return max(0, min($failedAt) + $this->windowSeconds - $now);
    }
}
