<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Api;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ReturnRequestRepositoryInterface
{
    /**
     * @throws CouldNotSaveException
     */
    public function save(ReturnRequestInterface $request): ReturnRequestInterface;

    /**
     * @throws NoSuchEntityException
     */
    public function getById(int $id): ReturnRequestInterface;

    /**
     * @return ReturnRequestInterface[] newest first
     */
    public function getByOrderId(int $orderId): array;
}
