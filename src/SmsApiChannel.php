<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;

final readonly class SmsApiChannel
{
    public function __construct(private SmsApiContract $smsApi) {}

    public function send($notifiable, Notification $notification): ?SmsApiResponse
    {
        $message = $notification->toSmsApi($notifiable);

        if (! $message instanceof SmsApiMessage) {
            return null;
        }

        if ($message->recipientNotGiven()) {
            $recipient = $notifiable->routeNotificationFor('smsApi', $notification);

            is_array($recipient)
                ? $message->toMany($recipient)
                : $message->to($recipient);
        }

        return $this->smsApi->send($message);
    }
}
