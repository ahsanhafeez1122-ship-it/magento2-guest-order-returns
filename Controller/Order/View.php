<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Order;

use AhsanHafeez\GuestReturns\Model\Config;
use AhsanHafeez\GuestReturns\Model\GuestSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * GET /guest-returns/order/view: order status, tracking and the return form.
 */
class View implements HttpGetActionInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly GuestSession $guestSession,
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->redirectFactory->create()->setPath('/');
        }

        if ($this->guestSession->getAuthorizedOrderId(time()) === null) {
            $this->messageManager->addNoticeMessage(__('Please look up your order again.'));
            return $this->redirectFactory->create()->setPath('guest-returns/lookup');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Your Order'));

        return $page;
    }
}
