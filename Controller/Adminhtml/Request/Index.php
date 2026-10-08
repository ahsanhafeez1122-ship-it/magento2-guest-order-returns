<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Adminhtml\Request;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Sales > Operations > Guest Returns
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'AhsanHafeez_GuestReturns::requests';

    public function __construct(
        Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('AhsanHafeez_GuestReturns::requests');
        $page->getConfig()->getTitle()->prepend(__('Guest Returns'));

        return $page;
    }
}
