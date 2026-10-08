<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest as ReturnRequestResource;
use Magento\Framework\Model\AbstractModel;

class ReturnRequest extends AbstractModel implements ReturnRequestInterface
{
    protected $_eventPrefix = 'ahsan_guest_return_request';

    protected function _construct(): void
    {
        $this->_init(ReturnRequestResource::class);
    }

    public function getOrderId(): int
    {
        return (int) $this->getData(self::ORDER_ID);
    }

    public function setOrderId(int $orderId): ReturnRequestInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getOrderIncrementId(): string
    {
        return (string) $this->getData(self::ORDER_INCREMENT_ID);
    }

    public function setOrderIncrementId(string $incrementId): ReturnRequestInterface
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $incrementId);
    }

    public function getStoreId(): int
    {
        return (int) $this->getData(self::STORE_ID);
    }

    public function setStoreId(int $storeId): ReturnRequestInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    public function getCustomerEmail(): string
    {
        return (string) $this->getData(self::CUSTOMER_EMAIL);
    }

    public function setCustomerEmail(string $email): ReturnRequestInterface
    {
        return $this->setData(self::CUSTOMER_EMAIL, $email);
    }

    public function getCustomerName(): ?string
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    public function setCustomerName(?string $name): ReturnRequestInterface
    {
        return $this->setData(self::CUSTOMER_NAME, $name);
    }

    public function getReason(): string
    {
        return (string) $this->getData(self::REASON);
    }

    public function setReason(string $reason): ReturnRequestInterface
    {
        return $this->setData(self::REASON, $reason);
    }

    public function getComment(): ?string
    {
        return $this->getData(self::COMMENT);
    }

    public function setComment(?string $comment): ReturnRequestInterface
    {
        return $this->setData(self::COMMENT, $comment);
    }

    public function getItemsSummary(): ?string
    {
        return $this->getData(self::ITEMS_SUMMARY);
    }

    public function setItemsSummary(?string $summary): ReturnRequestInterface
    {
        return $this->setData(self::ITEMS_SUMMARY, $summary);
    }

    public function getStatus(): string
    {
        return (string) ($this->getData(self::STATUS) ?: self::STATUS_PENDING);
    }

    public function setStatus(string $status): ReturnRequestInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }
}
