<?php

namespace Juszczyk\OrderNotification\Model;

use Exception;
use Juszczyk\OrderNotification\Api\ChannelInterface;
use Juszczyk\OrderNotification\Api\NotifierInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Notifier implements NotifierInterface
{
    const XML_PATH_ALLOWED_CUSTOMER_GROUPS = 'order_notification/general/customer_groups';

    protected array $channels;

    protected LoggerInterface $logger;

    protected ScopeConfigInterface $scopeConfig;

    protected StoreManagerInterface $storeManager;

    /**
     * @param array $channels
     * @param LoggerInterface $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        array $channels,
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->channels = $channels;
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function notify(Order $order): void
    {
        try {
            if (! $this->isNotificationAllowedForCustomerGroup($order)) {
                return;
            }
            /** @var ChannelInterface $channel */
            foreach ($this->channels as $channel) {
                if (! $channel->isEnabled()) {
                    continue;
                }
                $channel->send($order);
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->error($e->getTraceAsString());
        }
    }

    /**
     * @param Order $order
     * @return bool
     * @throws NoSuchEntityException
     */
    protected function isNotificationAllowedForCustomerGroup(Order $order): bool
    {
        $customerGroupId = (int)$order->getCustomerGroupId();
        $allowedCustomerGroups = explode(
            ",",
            $this->scopeConfig->getValue(
                self::XML_PATH_ALLOWED_CUSTOMER_GROUPS,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            )
        );

        if (in_array(GroupInterface::CUST_GROUP_ALL, $allowedCustomerGroups) ||
            in_array($customerGroupId, $allowedCustomerGroups)) {
            return true;
        }

        return false;
    }
}