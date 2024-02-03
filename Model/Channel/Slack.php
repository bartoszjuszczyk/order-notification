<?php

namespace Juszczyk\OrderNotification\Model\Channel;

use Juszczyk\OrderNotification\Model\AbstractChannel;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Slack extends AbstractChannel
{
    const CHANNEL_CODE = 'slack';

    const XML_PATH_SLACK_WEBHOOK_URL = 'order_notification/channels/slack/webhook_url';

    protected Curl $curl;

    protected Data $pricingHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        ManagerInterface $eventManager,
        Curl $curl,
        Data $pricingHelper
    ) {
        parent::__construct($scopeConfig, $storeManager, $eventManager);
        $this->curl = $curl;
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
        $slackMessage = $this->prepareSlackMessage($orderData);
        $slackWebhookUrl = $this->getSlackWebhookUrl();

        $this->curl->addHeader('Content-type', 'application/json');
        $this->curl->post($slackWebhookUrl, $slackMessage);
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

    protected function prepareSlackMessage(array $orderData): string
    {
        return '
        {
	        "blocks": [
	        	{
	        		"type": "header",
	        		"text": {
	        			"type": "plain_text",
	        			"text": "New order number ' . $orderData['increment_id'] . ' from ' . $orderData['store_name'] . ' :heart:",
	        			"emoji": true
	        		}
	        	},
	        	{
	        		"type": "divider"
	        	},
	        	{
	        		"type": "rich_text",
	        		"elements": [
	        			{
	        				"type": "rich_text_section",
	        				"elements": [
	        					{
	        						"type": "text",
	        						"text": "Order data: \n"
	        					}
	        				]
	        			},
	        			{
	        				"type": "rich_text_list",
	        				"style": "bullet",
	        				"elements": [
	        					{
	        						"type": "rich_text_section",
	        						"elements": [
	        							{
	        								"type": "text",
	        								"text": "Increment ID: "
	        							},
	        							{
	        								"type": "text",
	        								"text": "' . $orderData['increment_id'] . '"
	        							}
	        						]
	        					},
	        					{
	        						"type": "rich_text_section",
	        						"elements": [
	        							{
	        								"type": "text",
	        								"text": "Store Name: "
	        							},
	        							{
	        								"type": "text",
	        								"text": "' . $orderData['store_name'] . '"
	        							}
	        						]
	        					},
	        					{
	        						"type": "rich_text_section",
	        						"elements": [
	        							{
	        								"type": "text",
	        								"text": "Grand Total: "
	        							},
	        							{
	        								"type": "text",
	        								"text": "' . $orderData['grand_total'] . '"
	        							}
	        						]
	        					}
	        				]
	        			}
	        		]
	        	}
	        ]
        }';
    }

    protected function getSlackWebhookUrl(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SLACK_WEBHOOK_URL,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

}