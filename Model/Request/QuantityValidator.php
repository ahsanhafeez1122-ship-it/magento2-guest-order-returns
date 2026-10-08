<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Request;

/**
 * Checks the quantities a customer typed into the return form against what is still returnable.
 */
class QuantityValidator
{
    /**
     * @param array<int|string, mixed> $requested raw form input: order item id => qty
     * @param array<int, float> $returnable order item id => max qty
     */
    public function validate(array $requested, array $returnable): QuantityValidation
    {
        $errors = [];
        $lines = [];

        foreach ($requested as $itemId => $rawQty) {
            if ($rawQty === '' || $rawQty === null) {
                continue;
            }
            if (!is_numeric($rawQty) || !ctype_digit((string) $itemId)) {
                $errors[] = 'Please enter quantities as whole numbers.';
                continue;
            }

            $itemId = (int) $itemId;
            $qty = (float) $rawQty;

            if ($qty <= 0) {
                continue;
            }
            if (floor($qty) !== $qty) {
                $errors[] = 'Please enter quantities as whole numbers.';
                continue;
            }
            if (!array_key_exists($itemId, $returnable)) {
                $errors[] = 'One of the selected items cannot be returned.';
                continue;
            }
            if ($qty > $returnable[$itemId]) {
                $errors[] = sprintf('You can return at most %s of one of the selected items.', $this->formatQty($returnable[$itemId]));
                continue;
            }

            $lines[$itemId] = $qty;
        }

        if ($errors === [] && $lines === []) {
            $errors[] = 'Choose at least one item to return.';
        }

        return new QuantityValidation(array_values(array_unique($errors)), $errors === [] ? $lines : []);
    }

    private function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
    }
}
