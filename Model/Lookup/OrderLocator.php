<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Lookup;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Finds an order only when the order number, email and billing postcode all match.
 */
class OrderLocator
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function locate(LookupInput $input): ?OrderInterface
    {
        $criteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::INCREMENT_ID, $input->incrementId)
            ->addFilter(OrderInterface::STORE_ID, (int) $this->storeManager->getStore()->getId())
            ->setPageSize(1)
            ->create();

        foreach ($this->orderRepository->getList($criteria)->getItems() as $order) {
            if ($this->matches($order, $input)) {
                return $order;
            }
        }

        return null;
    }

    public function matches(OrderInterface $order, LookupInput $input): bool
    {
        $billing = $order->getBillingAddress();
        if ($billing === null) {
            return false;
        }

        $emailMatches = hash_equals(strtolower((string) $order->getCustomerEmail()), $input->email);
        $postcodeMatches = hash_equals(
            LookupInputValidator::normalizePostcode((string) $billing->getPostcode()),
            $input->postcode
        );

        // Both comparisons run before returning, so timing doesn't reveal which field was wrong.
        return $emailMatches && $postcodeMatches;
    }
}
