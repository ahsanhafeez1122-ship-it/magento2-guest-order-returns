<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Service;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use AhsanHafeez\GuestReturns\Api\ReturnRequestRepositoryInterface;
use AhsanHafeez\GuestReturns\Model\Config;
use AhsanHafeez\GuestReturns\Model\Eligibility\EligibilityChecker;
use AhsanHafeez\GuestReturns\Model\Eligibility\ItemSnapshot;
use AhsanHafeez\GuestReturns\Model\Eligibility\SnapshotFactory;
use AhsanHafeez\GuestReturns\Model\Notifier;
use AhsanHafeez\GuestReturns\Model\Request\QuantityValidator;
use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnItem as ReturnItemResource;
use AhsanHafeez\GuestReturns\Model\ReturnRequestFactory;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Throwable;

/**
 * Creates a return request. Quantities are re-checked inside a transaction with the order row locked,
 * so two tabs submitting at the same time can't return more than was shipped.
 */
class ReturnSubmitter
{
    private const MAX_COMMENT_LENGTH = 1000;

    public function __construct(
        private readonly Config $config,
        private readonly SnapshotFactory $snapshotFactory,
        private readonly EligibilityChecker $eligibilityChecker,
        private readonly QuantityValidator $quantityValidator,
        private readonly ReturnRequestFactory $requestFactory,
        private readonly ReturnRequestRepositoryInterface $repository,
        private readonly ReturnItemResource $itemResource,
        private readonly ResourceConnection $resourceConnection,
        private readonly Notifier $notifier
    ) {
    }

    /**
     * @param array<int|string, mixed> $requestedQty raw form input: order item id => qty
     * @throws LocalizedException with a customer-facing message
     */
    public function submit(Order $order, array $requestedQty, string $reason, string $comment): ReturnRequestInterface
    {
        $storeId = (int) $order->getStoreId();
        $reason = trim($reason);
        $comment = trim($comment);

        if (!in_array($reason, $this->config->getReasons($storeId), true)) {
            throw new LocalizedException(__('Choose a reason for the return.'));
        }
        if (mb_strlen($comment) > self::MAX_COMMENT_LENGTH) {
            throw new LocalizedException(__('Please keep the comment under %1 characters.', self::MAX_COMMENT_LENGTH));
        }

        $connection = $this->resourceConnection->getConnection('sales');
        $connection->beginTransaction();
        try {
            $connection->fetchOne(
                $connection->select()
                    ->from($this->resourceConnection->getTableName('sales_order'), ['entity_id'])
                    ->where('entity_id = ?', (int) $order->getId())
                    ->forUpdate(true)
            );

            $snapshot = $this->snapshotFactory->create($order);
            $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
            $eligibility = $this->eligibilityChecker->check($snapshot, $this->config->getPolicy($storeId), $now);
            if (!$eligibility->eligible) {
                throw new LocalizedException(__($eligibility->customerMessage()));
            }

            $validation = $this->quantityValidator->validate($requestedQty, $eligibility->returnableQty);
            if (!$validation->isValid()) {
                throw new LocalizedException(__(implode(' ', $validation->errors)));
            }

            $lines = $this->buildLines($snapshot->items, $validation->lines);

            $request = $this->requestFactory->create();
            $request->setOrderId((int) $order->getId())
                ->setOrderIncrementId((string) $order->getIncrementId())
                ->setStoreId($storeId)
                ->setCustomerEmail((string) $order->getCustomerEmail())
                ->setCustomerName(trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()) ?: null)
                ->setReason($reason)
                ->setComment($comment !== '' ? $comment : null)
                ->setItemsSummary($this->summarize($lines))
                ->setStatus(ReturnRequestInterface::STATUS_PENDING);
            $this->repository->save($request);
            $this->itemResource->insertLines((int) $request->getId(), $lines);

            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->notifier->notifyCreated($request, $lines);

        return $request;
    }

    /**
     * @param ItemSnapshot[] $items
     * @param array<int, float> $qtyByItem
     * @return array<int, array{order_item_id:int, sku:string, name:string, qty:float}>
     */
    private function buildLines(array $items, array $qtyByItem): array
    {
        $lines = [];
        foreach ($items as $item) {
            if (isset($qtyByItem[$item->itemId])) {
                $lines[] = [
                    'order_item_id' => $item->itemId,
                    'sku' => $item->sku,
                    'name' => $item->name,
                    'qty' => $qtyByItem[$item->itemId],
                ];
            }
        }

        return $lines;
    }

    /**
     * "2 × SKU-1, 1 × SKU-2" for the admin grid.
     *
     * @param array<int, array{sku:string, qty:float}> $lines
     */
    private function summarize(array $lines): string
    {
        $parts = array_map(static fn (array $l): string => sprintf('%d × %s', (int) $l['qty'], $l['sku']), $lines);

        return mb_substr(implode(', ', $parts), 0, 1024);
    }
}
