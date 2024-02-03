<?php

namespace Juszczyk\OrderNotification\Model\Channel;

use Juszczyk\OrderNotification\Model\AbstractChannel;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Email extends AbstractChannel
{
    const CHANNEL_CODE = 'email';

    const EMAIL_TEMPLATE_ID = 'order_notification';

    const XML_PATH_RECIPIENT_EMAIL_ADDRESSES = 'order_notification/channels/email/recipient_email_addresses';

    protected TransportBuilder $transportBuilder;

    protected StateInterface $inlineTranslation;

    protected Data $pricingHelper;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param Data $pricingHelper
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        Data $pricingHelper
    ) {
        parent::__construct($scopeConfig, $storeManager, $eventManager);
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->pricingHelper = $pricingHelper;
    }

    /**
     * @inheritDoc
     */
    public function send(Order $order): void
    {
        $orderData = $this->prepareData($order);
        $this->eventManager->dispatch(
            'order_notification_' . self::CHANNEL_CODE . '_send_before',
            [
                'order' => $order,
                'order_data' => $orderData
            ]
        );
        $storeId = $this->storeManager->getStore()->getId();
        $this->inlineTranslation->suspend();
        $this->transportBuilder->setTemplateIdentifier(self::EMAIL_TEMPLATE_ID)
            ->setTemplateOptions(
                [
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId
                ]
            )
            ->setTemplateVars($orderData)
            ->setFromByScope(
                'general',
                $storeId
            )
            ->addTo($this->getRecipientEmails());

        $this->transportBuilder->getTransport()->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * @inheritDoc
     */
    protected function prepareData(Order $order): array
    {
        return [
            'increment_id' => $order->getIncrementId(),
            'store_name' => $order->getStore()->getName(),
            'grand_total' => $this->pricingHelper->currencyByStore(
                $order->getGrandTotal(),
                $this->storeManager->getStore()->getId(),
                true,
                false
            )
        ];
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getRecipientEmails(): array
    {
        $recipientEmails = $this->scopeConfig->getValue(
            self::XML_PATH_RECIPIENT_EMAIL_ADDRESSES,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        if ($recipientEmails === null) {
            return [];
        }

        return explode(",", $recipientEmails);
    }
}