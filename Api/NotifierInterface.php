<?php

namespace Juszczyk\OrderNotification\Api;

use Magento\Sales\Model\Order;

interface NotifierInterface
{
    /**
     * @param Order $order
     * @return void
     */
    public function notify(Order $order): void;
}