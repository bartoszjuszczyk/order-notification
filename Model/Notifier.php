<?php

namespace Juszczyk\OrderNotification\Model;

use Exception;
use Juszczyk\OrderNotification\Api\ChannelInterface;
use Juszczyk\OrderNotification\Api\NotifierInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

class Notifier implements NotifierInterface
{
    protected array $channels;

    protected LoggerInterface $logger;

    /**
     * @param array $channels
     * @param LoggerInterface $logger
     */
    public function __construct(
        array $channels,
        LoggerInterface $logger
    ) {
        $this->channels = $channels;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function notify(Order $order): void
    {
        try {
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
}