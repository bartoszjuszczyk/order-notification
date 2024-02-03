<?php

namespace Juszczyk\OrderNotification\Api;

use Magento\Sales\Model\Order;

interface ChannelInterface
{
    /**
     * @param Order $order
     * @return void
     */
    public function send(Order $order): void;

    /**
     * @return bool
     */
    public function isEnabled(): bool;
}