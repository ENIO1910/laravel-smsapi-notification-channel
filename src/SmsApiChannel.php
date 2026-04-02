<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;

final readonly class SmsApiChannel
{
    private const string LEGACY_ROUTE_NAME = 'smsApi';

    public function __construct(private SmsApiContract $smsApi) {}

    public function send(object $notifiable, Notification $notification): ?SmsApiResponse
    {
        $message = $notification->toSmsApi($notifiable);

        if (! $message instanceof SmsApiMessage) {
            return null;
        }

        if ($message->recipientNotGiven()) {
            $recipient = $this->resolveRecipient($notifiable, $notification);

            is_array($recipient)
                ? $message->toMany($recipient)
                : $message->to($recipient);
        }

        return $this->smsApi->send($message);
    }

    private function resolveRecipient(object $notifiable, Notification $notification): mixed
    {
        return $notifiable->routeNotificationFor(self::class, $notification)
            ?? $notifiable->routeNotificationFor(self::LEGACY_ROUTE_NAME, $notification);
    }
}
