# Juszczyk_OrderNotification module

The Juszczyk_OrderNotification module allows you to sending notifications to different channels when a new order is place.

## Installation details

The Juszczyk_OrderNotification module does not make any changes in database.

To install the module:

1. Go to your Magento installation directory
2. Run command: `composer require juszczyk/module-order-notification`
3. Run command: `bin/magento module:enable Juszczyk_OrderNotification`
4. Run command: `bin/magento setup:upgrade`
5. Run command: `bin/magentp setup:static-content:deploy`

For information about a module installation,
see [Enable or disable modules](https://experienceleague.adobe.com/docs/commerce-operations/installation-guide/tutorials/manage-modules.html).

## Usage

The Juszczyk_OrderNotification module adds functionality to send notifications of order placement through various channels.
The default channels are email, Slack, WhatsApp, SMS.

## Configuration

Configuration of the module is located on the Sales tab in Order Notification section.

The General group has fields that allow you to enable/disable the module and to select groups of customers whose orders will be subject to sending notifications.

The Channels group contains specific configuration for each channel and the ability to enable/disable them.

## Structure

For information about a typical file structure of a module in Magento 2, see [Module file structure](https://developer.adobe.com/commerce/php/development/build/component-file-structure/#module-file-structure).

## Extensibility

Extension developers can interact with the Juszczyk_OrderNotification module.

Developers can add their own notification feeds by following these instructions:

1. Add new channel model that extends `Juszczyk\Model\AbstractChannel` and implements required methods.
2. Add previously created channel to `etc/di.xml` file by following the default channels created.
3. Add channel configuration to `etc/adminhtml/system.xml` with required `enable` field.

## Events

The module Juszczyk_OrderNotification dispatches the following events:

- `order_notification_{{CHANNEL_CODE}}_send_before` event in channels model (`Model\Channel\...`) method `send(Order $order)`. Parameters:
    - `order` is an order on which notification will be sent (`Magento\Sales\Model\Order`)
    - `order_data` is a order data that was prepared to send for the notification (`array`)

For information about an event in Magento 2, see [Events and observers](https://developer.adobe.com/commerce/php/development/components/events-and-observers/#events).
