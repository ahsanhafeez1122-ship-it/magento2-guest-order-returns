<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model;

use AhsanHafeez\GuestReturns\Model\Eligibility\ReturnPolicy;
use AhsanHafeez\GuestReturns\Model\Lookup\AttemptLimiter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_ENABLED = 'guest_returns/general/enabled';
    private const XML_WINDOW_DAYS = 'guest_returns/general/return_window_days';
    private const XML_REASONS = 'guest_returns/general/reasons';
    private const XML_MAX_ATTEMPTS = 'guest_returns/security/max_attempts';
    private const XML_ATTEMPT_WINDOW = 'guest_returns/security/attempt_window_minutes';
    private const XML_SESSION_LIFETIME = 'guest_returns/security/session_lifetime_minutes';
    private const XML_EMAIL_ENABLED = 'guest_returns/email/enabled';
    private const XML_EMAIL_IDENTITY = 'guest_returns/email/sender_identity';
    private const XML_EMAIL_CUSTOMER_TEMPLATE = 'guest_returns/email/customer_template';
    private const XML_EMAIL_STATUS_TEMPLATE = 'guest_returns/email/status_template';
    private const XML_EMAIL_ADMIN_TEMPLATE = 'guest_returns/email/admin_template';
    private const XML_EMAIL_ADMIN_RECIPIENT = 'guest_returns/email/admin_recipient';

    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getPolicy(?int $storeId = null): ReturnPolicy
    {
        $days = (int) $this->scopeConfig->getValue(self::XML_WINDOW_DAYS, ScopeInterface::SCOPE_STORE, $storeId);

        return new ReturnPolicy(max(1, $days));
    }

    /**
     * @return string[]
     */
    public function getReasons(?int $storeId = null): array
    {
        $raw = (string) $this->scopeConfig->getValue(self::XML_REASONS, ScopeInterface::SCOPE_STORE, $storeId);
        $lines = array_map('trim', preg_split('/\R/', $raw) ?: []);

        return array_values(array_filter($lines, static fn (string $line): bool => $line !== ''));
    }

    public function getAttemptLimiter(): AttemptLimiter
    {
        $max = max(1, (int) $this->scopeConfig->getValue(self::XML_MAX_ATTEMPTS));
        $minutes = max(1, (int) $this->scopeConfig->getValue(self::XML_ATTEMPT_WINDOW));

        return new AttemptLimiter($max, $minutes * 60);
    }

    public function getSessionLifetimeSeconds(): int
    {
        return max(1, (int) $this->scopeConfig->getValue(self::XML_SESSION_LIFETIME)) * 60;
    }

    public function isEmailEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_EMAIL_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getSenderIdentity(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_EMAIL_IDENTITY, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getCustomerTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_EMAIL_CUSTOMER_TEMPLATE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getStatusTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_EMAIL_STATUS_TEMPLATE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAdminTemplate(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_EMAIL_ADMIN_TEMPLATE, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAdminRecipient(?int $storeId = null): string
    {
        return trim((string) $this->scopeConfig->getValue(self::XML_EMAIL_ADMIN_RECIPIENT, ScopeInterface::SCOPE_STORE, $storeId));
    }
}
