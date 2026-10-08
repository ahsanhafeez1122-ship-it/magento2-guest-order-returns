<?php
declare(strict_types=1);

namespace AhsanHafeez\GuestReturns\Model;

use AhsanHafeez\GuestReturns\Api\Data\ReturnRequestInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Sends the return emails. A failed email is logged and never blocks the return itself.
 */
class Notifier
{
    public function __construct(
        private readonly Config $config,
        private readonly TransportBuilder $transportBuilder,
        private readonly StateInterface $inlineTranslation,
        private readonly Escaper $escaper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<int, array{sku:string, name:string, qty:float}> $lines
     */
    public function notifyCreated(ReturnRequestInterface $request, array $lines): void
    {
        $storeId = $request->getStoreId();
        if (!$this->config->isEmailEnabled($storeId)) {
            return;
        }

        $vars = $this->baseVars($request) + ['items_html' => $this->itemsHtml($lines)];

        $this->send($this->config->getCustomerTemplate($storeId), $request->getCustomerEmail(), (string) $request->getCustomerName(), $vars, $storeId);

        $adminRecipient = $this->config->getAdminRecipient($storeId);
        if ($adminRecipient !== '') {
            $this->send($this->config->getAdminTemplate($storeId), $adminRecipient, '', $vars, $storeId);
        }
    }

    public function notifyStatusChanged(ReturnRequestInterface $request): void
    {
        $storeId = $request->getStoreId();
        if (!$this->config->isEmailEnabled($storeId)) {
            return;
        }

        $this->send(
            $this->config->getStatusTemplate($storeId),
            $request->getCustomerEmail(),
            (string) $request->getCustomerName(),
            $this->baseVars($request),
            $storeId
        );
    }

    /**
     * Scalars only, so the templates work with Magento's strict template variable mode.
     *
     * @return array<string, string|int>
     */
    private function baseVars(ReturnRequestInterface $request): array
    {
        return [
            'request_id' => (int) $request->getId(),
            'order_increment_id' => $request->getOrderIncrementId(),
            'customer_name' => (string) ($request->getCustomerName() ?: __('Customer')),
            'customer_email' => $request->getCustomerEmail(),
            'reason' => $request->getReason(),
            'comment' => (string) $request->getComment(),
            'status_label' => (string) Source\Status::label($request->getStatus()),
        ];
    }

    /**
     * @param array<int, array{sku:string, name:string, qty:float}> $lines
     */
    private function itemsHtml(array $lines): string
    {
        $rows = '';
        foreach ($lines as $line) {
            $rows .= sprintf(
                '<tr><td style="padding:6px 12px 6px 0">%s</td><td style="padding:6px 12px 6px 0">%s</td><td style="padding:6px 0;text-align:right">%d</td></tr>',
                $this->escaper->escapeHtml($line['name']),
                $this->escaper->escapeHtml($line['sku']),
                (int) $line['qty']
            );
        }

        return '<table style="border-collapse:collapse">' . $rows . '</table>';
    }

    /**
     * @param array<string, string|int> $vars
     */
    private function send(string $templateId, string $email, string $name, array $vars, int $storeId): void
    {
        if ($templateId === '' || $email === '') {
            return;
        }

        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(['area' => Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFromByScope($this->config->getSenderIdentity($storeId), $storeId)
                ->addTo($email, $name)
                ->getTransport()
                ->sendMessage();
        } catch (Throwable $e) {
            $this->logger->error('Guest returns: could not send email', [
                'template' => $templateId,
                'request_id' => $vars['request_id'] ?? null,
                'exception' => $e,
            ]);
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
