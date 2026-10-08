<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model;

use Magento\Framework\Session\SessionManagerInterface;

/**
 * Remembers which order the visitor proved they own, and their recent failed lookups.
 */
class GuestSession
{
    private const KEY_ORDER = 'ahsan_guest_returns_order';
    private const KEY_ATTEMPTS = 'ahsan_guest_returns_failed_lookups';

    public function __construct(
        private readonly SessionManagerInterface $session,
        private readonly Config $config
    ) {
    }

    public function authorize(int $orderId, int $now): void
    {
        $this->session->setData(self::KEY_ORDER, [
            'order_id' => $orderId,
            'expires_at' => $now + $this->config->getSessionLifetimeSeconds(),
        ]);
    }

    public function getAuthorizedOrderId(int $now): ?int
    {
        $data = $this->session->getData(self::KEY_ORDER);
        if (!is_array($data) || !isset($data['order_id'], $data['expires_at'])) {
            return null;
        }
        if ((int) $data['expires_at'] < $now) {
            $this->clear();
            return null;
        }

        return (int) $data['order_id'];
    }

    public function clear(): void
    {
        $this->session->unsetData(self::KEY_ORDER);
    }

    /**
     * @return int[]
     */
    public function getFailedLookups(): array
    {
        $data = $this->session->getData(self::KEY_ATTEMPTS);

        return is_array($data) ? array_map('intval', $data) : [];
    }

    /**
     * @param int[] $timestamps
     */
    public function setFailedLookups(array $timestamps): void
    {
        $this->session->setData(self::KEY_ATTEMPTS, array_values($timestamps));
    }
}
