<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Api\Data;

interface ReturnRequestInterface
{
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const ORDER_INCREMENT_ID = 'order_increment_id';
    public const STORE_ID = 'store_id';
    public const CUSTOMER_EMAIL = 'customer_email';
    public const CUSTOMER_NAME = 'customer_name';
    public const REASON = 'reason';
    public const COMMENT = 'comment';
    public const ITEMS_SUMMARY = 'items_summary';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    /**
     * @return int|null
     */
    public function getId();

    public function getOrderId(): int;

    public function setOrderId(int $orderId): self;

    public function getOrderIncrementId(): string;

    public function setOrderIncrementId(string $incrementId): self;

    public function getStoreId(): int;

    public function setStoreId(int $storeId): self;

    public function getCustomerEmail(): string;

    public function setCustomerEmail(string $email): self;

    public function getCustomerName(): ?string;

    public function setCustomerName(?string $name): self;

    public function getReason(): string;

    public function setReason(string $reason): self;

    public function getComment(): ?string;

    public function setComment(?string $comment): self;

    public function getItemsSummary(): ?string;

    public function setItemsSummary(?string $summary): self;

    public function getStatus(): string;

    public function setStatus(string $status): self;

    public function getCreatedAt(): ?string;
}
