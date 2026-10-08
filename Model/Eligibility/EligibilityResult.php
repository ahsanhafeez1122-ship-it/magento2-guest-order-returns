<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

use DateTimeImmutable;

final class EligibilityResult
{
    public const REASON_STATE = 'order_state';
    public const REASON_NOT_SHIPPED = 'not_shipped';
    public const REASON_EXPIRED = 'window_expired';
    public const REASON_NOTHING_LEFT = 'nothing_returnable';

    /**
     * @param array<int, float> $returnableQty order item id => qty the customer may still return
     */
    private function __construct(
        public readonly bool $eligible,
        public readonly ?string $reason,
        public readonly array $returnableQty,
        public readonly ?DateTimeImmutable $deadline
    ) {
    }

    /**
     * @param array<int, float> $returnableQty
     */
    public static function eligible(array $returnableQty, DateTimeImmutable $deadline): self
    {
        return new self(true, null, $returnableQty, $deadline);
    }

    public static function denied(string $reason, ?DateTimeImmutable $deadline = null): self
    {
        return new self(false, $reason, [], $deadline);
    }

    /**
     * Text for the storefront. Kept here so the controller and the template say the same thing.
     */
    public function customerMessage(): string
    {
        return match ($this->reason) {
            null => 'This order can be returned.',
            self::REASON_STATE => 'This order can no longer be returned online. Please contact us.',
            self::REASON_NOT_SHIPPED => 'Nothing on this order has shipped yet, so there is nothing to return.',
            self::REASON_EXPIRED => 'The return window for this order has closed.',
            self::REASON_NOTHING_LEFT => 'Every item on this order has already been returned or refunded.',
            default => 'This order cannot be returned online.',
        };
    }
}
