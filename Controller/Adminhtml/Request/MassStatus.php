<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Adminhtml\Request;

use AhsanHafeez\GuestReturns\Api\ReturnRequestRepositoryInterface;
use AhsanHafeez\GuestReturns\Model\Notifier;
use AhsanHafeez\GuestReturns\Model\ResourceModel\ReturnRequest\CollectionFactory;
use AhsanHafeez\GuestReturns\Model\Source\Status;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;
use Throwable;

/**
 * Grid mass action: approve / reject / complete the selected requests and email each customer.
 */
class MassStatus extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'AhsanHafeez_GuestReturns::update_status';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly ReturnRequestRepositoryInterface $repository,
        private readonly Notifier $notifier
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $redirect = $this->resultRedirectFactory->create()->setPath('*/*/index');
        $status = (string) $this->getRequest()->getParam('status');

        if (!Status::isValid($status)) {
            $this->messageManager->addErrorMessage(__('Unknown status "%1".', $status));
            return $redirect;
        }

        $updated = 0;
        $failed = 0;
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            foreach ($collection->getItems() as $request) {
                if ($request->getStatus() === $status) {
                    continue;
                }
                try {
                    $request->setStatus($status);
                    $this->repository->save($request);
                    $this->notifier->notifyStatusChanged($request);
                    $updated++;
                } catch (Throwable) {
                    $failed++;
                }
            }
        } catch (Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect;
        }

        if ($updated > 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 return request(s) marked as %2. Customers have been emailed.', $updated, Status::label($status))
            );
        }
        if ($failed > 0) {
            $this->messageManager->addErrorMessage(__('%1 return request(s) could not be updated.', $failed));
        }
        if ($updated === 0 && $failed === 0) {
            $this->messageManager->addNoticeMessage(__('The selected requests already have that status.'));
        }

        return $redirect;
    }
}
