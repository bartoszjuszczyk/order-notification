<?php

namespace Juszczyk\OrderNotification\Model\Channel;

use Juszczyk\OrderNotification\Model\AbstractChannel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class WhatsApp extends AbstractChannel
{
    const CHANNEL_CODE = 'whatsapp';

    const XML_PATH_WHATSAPP_TWILIO_ACCOUNT_SID = 'order_notification/channels/whatsapp/twilio_account_sid';
    const XML_PATH_WHATSAPP_TWILIO_AUTH_TOKEN = 'order_notification/channels/whatsapp/twilio_auth_token';
    const XML_PATH_WHATSAPP_TWILIO_PHONE_NUMBER = 'order_notification/channels/whatsapp/twilio_phone_number';
    const XML_PATH_WHATSAPP_RECIPIENT_PHONE_NUMBERS = 'order_notification/channels/whatsapp/recipient_phone_numbers';

    /**
     * @inheritDoc
     * @throws TwilioException|NoSuchEntityException
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
        $whatsappMessage = 'New order with number ' . $orderData['increment_id'] . ' in ' . $orderData['store_name'] .
            ' has been placed.';

        $twilioWhatsAppClient = $this->getTwilioWhatsAppClient();
        $twilioPhoneNumber = 'whatsapp:' . $this->getTwilioPhoneNumber();
        $recipientWhatsAppPhoneNumbers = $this->getRecipientWhatsAppPhoneNumbers();

        foreach ($recipientWhatsAppPhoneNumbers as $recipientWhatsAppPhoneNumber) {
            $twilioResponse = $twilioWhatsAppClient->messages->create(
                'whatsapp:' . $recipientWhatsAppPhoneNumber,
                [
                    'from' => $twilioPhoneNumber,
                    'body' => $whatsappMessage
                ]
            );
            $this->handleTwilioResponse($twilioResponse);
        }
    }

    /**
     * @inheritDoc
     */
    protected function prepareData(Order $order): array
    {
        return [
            'increment_id' => $order->getIncrementId(),
            'store_name' => $order->getStore()->getName()
        ];
    }

    /**
     * @return Client
     * @throws NoSuchEntityException
     * @throws ConfigurationException
     */
    protected function getTwilioWhatsAppClient(): Client
    {
        $accountSid = $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TWILIO_ACCOUNT_SID,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
        $authToken = $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TWILIO_AUTH_TOKEN,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );

        return new Client($accountSid, $authToken);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    protected function getTwilioPhoneNumber(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_WHATSAPP_TWILIO_PHONE_NUMBER,
            ScopeInterface::SCOPE_STORE,
            $this->storeManager->getStore()->getId()
        );
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    protected function getRecipientWhatsAppPhoneNumbers(): array
    {
        return explode(
            ",",
            $this->scopeConfig->getValue(
                self::XML_PATH_WHATSAPP_RECIPIENT_PHONE_NUMBERS,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore()->getId()
            )
        );
    }

    /**
     * @param $twilioResponse
     * @return void
     * @throws TwilioException
     */
    protected function handleTwilioResponse($twilioResponse): void
    {
        if ($twilioResponse->errorCode !== null) {
            throw new TwilioException($twilioResponse->errorMessage);
        }
    }
}