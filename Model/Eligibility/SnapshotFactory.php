<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\Eligibility;

use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnItem as ReturnItemResource;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Sales\Model\Order;

/**
 * Turns a Magento order into the plain snapshot the eligibility rules work on.
 */
class SnapshotFactory
{
    public function __construct(private readonly ReturnItemResource $returnItemResource)
    {
    }

    public function create(Order $order): OrderSnapshot
    {
        $requested = $this->returnItemResource->getRequestedQtyByOrderItem((int) $order->getId());

        $items = [];
        // Visible items only: for configurable/bundle products the parent line carries the shipped qty.
        foreach ($order->getAllVisibleItems() as $item) {
            $itemId = (int) $item->getItemId();
            $items[] = new ItemSnapshot(
                $itemId,
                (string) $item->getSku(),
                (string) $item->getName(),
                (float) $item->getQtyShipped(),
                (float) $item->getQtyRefunded(),
                $requested[$itemId] ?? 0.0,
                (bool) $item->getIsVirtual()
            );
        }

        return new OrderSnapshot(
            (string) $order->getIncrementId(),
            (string) $order->getState(),
            $this->lastShippedAt($order),
            $items
        );
    }

    private function lastShippedAt(Order $order): ?DateTimeImmutable
    {
        $latest = null;
        foreach ($order->getShipmentsCollection() as $shipment) {
            $createdAt = $shipment->getCreatedAt();
            if (!$createdAt) {
                continue;
            }
            // sales_shipment.created_at is stored in UTC.
            $date = new DateTimeImmutable((string) $createdAt, new DateTimeZone('UTC'));
            if ($latest === null || $date > $latest) {
                $latest = $date;
            }
        }

        return $latest;
    }
}
