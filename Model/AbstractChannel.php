<?php

namespace Juszczyk\OrderNotification\Model;

use Juszczyk\OrderNotification\Api\ChannelInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractChannel implements ChannelInterface
{
    const CHANNEL_CODE = '';

    protected ScopeConfigInterface $scopeConfig;

    protected StoreManagerInterface $storeManager;

    protected ManagerInterface $eventManager;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @param Order $order
     * @return void
     */
    abstract public function send(Order $order): void;

    /**
     * @param Order $order
     * @return array
     */
    abstract protected function prepareData(Order $order): array;

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isEnabled(): bool
    {
        if (! $this->isModuleEnabled()) {
            return false;
        }
        return (bool)$this->scopeConfig->getValue(
            'order_notification/channels/' . $this::CHANNEL_CODE . '/enabled',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    protected function isModuleEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(
            'order_notification/general/enabled',
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }
}