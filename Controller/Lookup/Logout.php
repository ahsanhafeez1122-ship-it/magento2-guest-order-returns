<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Lookup;

use AhsanHafeez\GuestReturns\Model\GuestSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * POST /guest-returns/lookup/logout: forget the verified order (shared computers).
 */
class Logout implements HttpPostActionInterface
{
    public function __construct(
        private readonly GuestSession $guestSession,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): Redirect
    {
        $this->guestSession->clear();

        return $this->redirectFactory->create()->setPath('guest-returns/lookup');
    }
}
