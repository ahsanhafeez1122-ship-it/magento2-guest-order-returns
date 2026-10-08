<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Returns;

use AhsanHafeez\GuestReturns\Model\Config;
use AhsanHafeez\GuestReturns\Model\GuestSession;
use AhsanHafeez\GuestReturns\Service\ReturnSubmitter;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * POST /guest-returns/returns/submit: create a return request for the verified order.
 */
class Submit implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly GuestSession $guestSession,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ReturnSubmitter $submitter,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();
        $orderId = $this->guestSession->getAuthorizedOrderId(time());
        if (!$this->config->isEnabled() || $orderId === null) {
            $this->messageManager->addNoticeMessage(__('Please look up your order again.'));
            return $redirect->setPath('guest-returns/lookup');
        }

        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            $qty = $this->request->getParam('qty');
            $request = $this->submitter->submit(
                $order,
                is_array($qty) ? $qty : [],
                (string) $this->request->getParam('reason'),
                (string) $this->request->getParam('comment')
            );
            $this->messageManager->addSuccessMessage(
                __('Return request #%1 sent. We have emailed you a copy and will be in touch with next steps.', $request->getId())
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (Throwable $e) {
            $this->logger->critical('Guest returns: submit failed', ['order_id' => $orderId, 'exception' => $e]);
            $this->messageManager->addErrorMessage(__('Something went wrong sending your return. Please try again or contact us.'));
        }

        return $redirect->setPath('guest-returns/order/view');
    }
}
