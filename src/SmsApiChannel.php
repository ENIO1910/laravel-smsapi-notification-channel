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
            $message->to($notifiable->routeNotificationFor('smsApi', $notification));
        }

        return $this->smsApi->send($message);
    }
}
