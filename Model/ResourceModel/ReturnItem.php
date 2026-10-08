<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model\ResourceModel;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Return lines are only ever written in bulk and read as plain rows, so there is no model class for them.
 */
class ReturnItem extends AbstractDb
{
    public const TABLE = 'ahsan_guest_return_item';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'entity_id');
    }

    /**
     * Qty already requested per order item, ignoring rejected requests.
     *
     * @return array<int, float> order item id => qty
     */
    public function getRequestedQtyByOrderItem(int $orderId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['item' => $this->getMainTable()], ['order_item_id', 'qty' => 'SUM(item.qty)'])
            ->join(
                ['request' => $this->getTable(ReturnRequest::TABLE)],
                'request.entity_id = item.request_id',
                []
            )
            ->where('request.order_id = ?', $orderId)
            ->where('request.status != ?', ReturnRequestInterface::STATUS_REJECTED)
            ->group('item.order_item_id');

        $result = [];
        foreach ($connection->fetchPairs($select) as $itemId => $qty) {
            $result[(int) $itemId] = (float) $qty;
        }

        return $result;
    }

    /**
     * @param array<int, array{order_item_id:int, sku:string, name:string, qty:float}> $lines
     */
    public function insertLines(int $requestId, array $lines): void
    {
        $rows = [];
        foreach ($lines as $line) {
            $rows[] = [
                'request_id' => $requestId,
                'order_item_id' => $line['order_item_id'],
                'sku' => $line['sku'],
                'name' => $line['name'],
                'qty' => $line['qty'],
            ];
        }
        if ($rows !== []) {
            $this->getConnection()->insertMultiple($this->getMainTable(), $rows);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getLinesByRequestId(int $requestId): array
    {
        $connection = $this->getConnection();

        return $connection->fetchAll(
            $connection->select()->from($this->getMainTable())->where('request_id = ?', $requestId)
        );
    }
}
