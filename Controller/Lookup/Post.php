<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Controller\Lookup;

use AhsanHafeez\GuestReturns\Model\Config;
use AhsanHafeez\GuestReturns\Model\GuestSession;
use AhsanHafeez\GuestReturns\Model\Lookup\LookupInputValidator;
use AhsanHafeez\GuestReturns\Model\Lookup\OrderLocator;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

/**
 * POST /guest-returns/lookup/post: checks order number + email + billing postcode.
 * Form key (CSRF) is validated by Magento for every HttpPostActionInterface controller.
 */
class Post implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly Config $config,
        private readonly GuestSession $guestSession,
        private readonly LookupInputValidator $validator,
        private readonly OrderLocator $orderLocator,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager
    ) {
    }

    public function execute(): Redirect
    {
        $redirect = $this->redirectFactory->create();
        if (!$this->config->isEnabled()) {
            return $redirect->setPath('/');
        }

        $now = time();
        $limiter = $this->config->getAttemptLimiter();
        $failed = $this->guestSession->getFailedLookups();

        if ($limiter->isBlocked($failed, $now)) {
            $minutes = (int) ceil($limiter->retryAfter($failed, $now) / 60);
            $this->messageManager->addErrorMessage(
                __('Too many attempts. Please try again in %1 minute(s).', max(1, $minutes))
            );
            return $redirect->setPath('guest-returns/lookup');
        }

        $input = $this->validator->normalize(
            (string) $this->request->getParam('order_number'),
            (string) $this->request->getParam('email'),
            (string) $this->request->getParam('postcode')
        );

        $errors = $this->validator->validate($input);
        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->messageManager->addErrorMessage(__($error));
            }
            return $redirect->setPath('guest-returns/lookup');
        }

        $order = $this->orderLocator->locate($input);
        if ($order === null) {
            $this->guestSession->setFailedLookups($limiter->registerFailure($failed, $now));
            // Same message whichever field was wrong, so the form can't be used to confirm emails or order numbers.
            $this->messageManager->addErrorMessage(
                __("We couldn't find an order with those details. Check the order number, email and billing postcode.")
            );
            return $redirect->setPath('guest-returns/lookup');
        }

        $this->guestSession->setFailedLookups([]);
        $this->guestSession->authorize((int) $order->getEntityId(), $now);

        return $redirect->setPath('guest-returns/order/view');
    }
}
