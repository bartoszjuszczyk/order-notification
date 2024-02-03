<?php

namespace Juszczyk\OrderNotification\Observer;

use Juszczyk\OrderNotification\Api\NotifierInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class NotifyAboutOrder implements ObserverInterface
{
    protected NotifierInterface $notifier;

    /**
     * @param NotifierInterface $notifier
     */
    public function __construct(
        NotifierInterface $notifier
    ) {
        $this->notifier = $notifier;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        $this->notifier->notify($order);
    }
}