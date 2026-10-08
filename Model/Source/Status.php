<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Source;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Phrase;

class Status implements OptionSourceInterface
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            ReturnRequestInterface::STATUS_PENDING => 'Pending review',
            ReturnRequestInterface::STATUS_APPROVED => 'Approved',
            ReturnRequestInterface::STATUS_REJECTED => 'Rejected',
            ReturnRequestInterface::STATUS_COMPLETED => 'Completed',
        ];
    }

    public static function label(string $status): Phrase
    {
        return __(self::labels()[$status] ?? $status);
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    public function toOptionArray(): array
    {
        $options = [];
        foreach (self::labels() as $value => $label) {
            $options[] = ['value' => $value, 'label' => __($label)];
        }

        return $options;
    }
}
