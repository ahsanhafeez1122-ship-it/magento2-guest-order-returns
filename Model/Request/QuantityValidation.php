<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Request;

final class QuantityValidation
{
    /**
     * @param string[] $errors
     * @param array<int, float> $lines order item id => qty to return (empty when there are errors)
     */
    public function __construct(
        public readonly array $errors,
        public readonly array $lines
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
