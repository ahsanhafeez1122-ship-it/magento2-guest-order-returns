<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Lookup;

use AhsanHafeez\GuestReturns\Model\Config;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * GET /guest-returns/lookup: the order lookup form.
 */
class Index implements HttpGetActionInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly PageFactory $pageFactory,
        private readonly ForwardFactory $forwardFactory
    ) {
    }

    public function execute(): ResultInterface
    {
        if (!$this->config->isEnabled()) {
            return $this->forwardFactory->create()->forward('noroute');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Find Your Order'));

        return $page;
    }
}
