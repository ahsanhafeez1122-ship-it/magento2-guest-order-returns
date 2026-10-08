<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use AhsanHafeez\GuestReturns\Api\ReturnRequestRepositoryInterface;
use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Throwable;

class ReturnRequestRepository implements ReturnRequestRepositoryInterface
{
    public function __construct(
        private readonly ReturnRequestResource $resource,
        private readonly ReturnRequestFactory $requestFactory,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    public function save(ReturnRequestInterface $request): ReturnRequestInterface
    {
        try {
            /** @var ReturnRequest $request */
            $this->resource->save($request);
        } catch (Throwable $e) {
            throw new CouldNotSaveException(__('Could not save the return request: %1', $e->getMessage()), $e);
        }

        return $request;
    }

    public function getById(int $id): ReturnRequestInterface
    {
        $request = $this->requestFactory->create();
        $this->resource->load($request, $id);
        if (!$request->getId()) {
            throw new NoSuchEntityException(__('Return request #%1 does not exist.', $id));
        }

        return $request;
    }

    public function getByOrderId(int $orderId): array
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(ReturnRequestInterface::ORDER_ID, $orderId)
            ->setOrder(ReturnRequestInterface::ENTITY_ID, 'DESC');

        return array_values($collection->getItems());
    }
}
