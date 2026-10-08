<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

use DateTimeImmutable;

/**
 * Decides whether an order can be returned and how much of each item is still returnable.
 */
class EligibilityChecker
{
    public function check(OrderSnapshot $order, ReturnPolicy $policy, DateTimeImmutable $now): EligibilityResult
    {
        if (!in_array($order->state, $policy->allowedStates, true)) {
            return EligibilityResult::denied(EligibilityResult::REASON_STATE);
        }

        if ($order->lastShippedAt === null) {
            return EligibilityResult::denied(EligibilityResult::REASON_NOT_SHIPPED);
        }

        $deadline = $order->lastShippedAt->modify(sprintf('+%d days', $policy->windowDays));
        if ($now > $deadline) {
            return EligibilityResult::denied(EligibilityResult::REASON_EXPIRED, $deadline);
        }

        $returnable = [];
        foreach ($order->items as $item) {
            $qty = $item->returnableQty();
            if ($qty > 0) {
                $returnable[$item->itemId] = $qty;
            }
        }

        if ($returnable === []) {
            return EligibilityResult::denied(EligibilityResult::REASON_NOTHING_LEFT, $deadline);
        }

        return EligibilityResult::eligible($returnable, $deadline);
    }
}
