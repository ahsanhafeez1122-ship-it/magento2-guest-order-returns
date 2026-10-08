<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\ViewModel;

use AhsanHafeez\GuestReturns\Api\ReturnRequestRepositoryInterface;
use AhsanHafeez\GuestReturns\Model\Config;
use AhsanHafeez\GuestReturns\Model\Eligibility\EligibilityChecker;
use AhsanHafeez\GuestReturns\Model\Eligibility\EligibilityResult;
use AhsanHafeez\GuestReturns\Model\Eligibility\SnapshotFactory;
use AhsanHafeez\GuestReturns\Model\GuestSession;
use AhsanHafeez\GuestReturns\Model\Source\Status;
use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Throwable;

class OrderView implements ArgumentInterface
{
    private ?Order $order = null;
    private ?EligibilityResult $eligibility = null;

    public function __construct(
        private readonly GuestSession $guestSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SnapshotFactory $snapshotFactory,
        private readonly EligibilityChecker $eligibilityChecker,
        private readonly ReturnRequestRepositoryInterface $returnRepository,
        private readonly Config $config,
        private readonly UrlInterface $urlBuilder,
        private readonly TimezoneInterface $timezone,
        private readonly PriceHelper $priceHelper
    ) {
    }

    public function getOrder(): ?Order
    {
        if ($this->order === null) {
            $orderId = $this->guestSession->getAuthorizedOrderId(time());
            if ($orderId === null) {
                return null;
            }
            try {
                /** @var Order $order */
                $order = $this->orderRepository->get($orderId);
                $this->order = $order;
            } catch (Throwable) {
                return null;
            }
        }

        return $this->order;
    }

    public function getEligibility(): EligibilityResult
    {
        if ($this->eligibility === null) {
            $order = $this->getOrder();
            $snapshot = $this->snapshotFactory->create($order);
            $this->eligibility = $this->eligibilityChecker->check(
                $snapshot,
                $this->config->getPolicy((int) $order->getStoreId()),
                new DateTimeImmutable('now', new DateTimeZone('UTC'))
            );
        }

        return $this->eligibility;
    }

    public function getReturnableQty(int $itemId): int
    {
        return (int) ($this->getEligibility()->returnableQty[$itemId] ?? 0);
    }

    public function formatDeadline(): ?string
    {
        $deadline = $this->getEligibility()->deadline;

        return $deadline ? $this->timezone->formatDate($deadline->format('Y-m-d H:i:s'), \IntlDateFormatter::LONG) : null;
    }

    public function formatDate(?string $utcDate): string
    {
        return $utcDate ? $this->timezone->formatDate($utcDate, \IntlDateFormatter::MEDIUM) : '';
    }

    public function formatPrice(float $amount): string
    {
        return (string) $this->priceHelper->currency($amount, true, false);
    }

    /**
     * @return array<int, array{carrier:string, number:string}>
     */
    public function getTracking(): array
    {
        $tracks = [];
        foreach ($this->getOrder()->getTracksCollection() as $track) {
            $tracks[] = [
                'carrier' => (string) $track->getTitle(),
                'number' => (string) $track->getTrackNumber(),
            ];
        }

        return $tracks;
    }

    /**
     * @return string[]
     */
    public function getReasons(): array
    {
        return $this->config->getReasons((int) $this->getOrder()->getStoreId());
    }

    /**
     * @return \AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface[]
     */
    public function getExistingRequests(): array
    {
        return $this->returnRepository->getByOrderId((int) $this->getOrder()->getId());
    }

    public function getStatusLabel(string $status): string
    {
        return (string) Status::label($status);
    }

    public function getSubmitUrl(): string
    {
        return $this->urlBuilder->getUrl('guest-returns/returns/submit');
    }

    public function getLogoutUrl(): string
    {
        return $this->urlBuilder->getUrl('guest-returns/lookup/logout');
    }
}
