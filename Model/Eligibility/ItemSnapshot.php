<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

/**
 * Plain copy of the order item fields the return rules need, so the rules can be tested without Magento.
 */
final class ItemSnapshot
{
    public function __construct(
        public readonly int $itemId,
        public readonly string $sku,
        public readonly string $name,
        public readonly float $qtyShipped,
        public readonly float $qtyRefunded = 0.0,
        public readonly float $qtyAlreadyRequested = 0.0,
        public readonly bool $isVirtual = false
    ) {
    }

    /**
     * Shipped, minus already refunded, minus already in an open/approved return request.
     */
    public function returnableQty(): float
    {
        if ($this->isVirtual) {
            return 0.0;
        }

        return max(0.0, $this->qtyShipped - $this->qtyRefunded - $this->qtyAlreadyRequested);
    }
}
