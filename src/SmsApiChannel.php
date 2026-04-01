<?php

namespace NotificationChannels\SmsApi;

use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;

class SmsApiChannel
{
    public function __construct(protected SmsApi $smsApi)
    {
    }

    public function send($notifiable, Notification $notification): ?SmsApiResponse
    {
        $message = $notification->toSmsApi($notifiable);

        if (! $message instanceof SmsApiMessage) {
            return null;
        }

        if ($message->recipientNotGiven()) {
            $message->to($notifiable->routeNotificationFor('smsapi', $notification));
        }

        return $this->smsApi->send($message);
    }
}
