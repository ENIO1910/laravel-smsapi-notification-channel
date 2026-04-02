<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Exceptions;

use Exception;
use Smsapi\Client\SmsapiClientException;

final class CouldNotSendNotification extends Exception
{
    public static function smsApiRespondedWithAnError(SmsapiClientException $exception): self
    {
        $description = $exception->getMessage();

        return new self(sprintf('SMSAPI responded with an error `%s`.', $description));
    }

    public static function couldNotCommunicateWithSmsApi(Exception $exception): self
    {
        return new self(sprintf('The communication with SMSAPI failed. `%s`.', $exception->getMessage()));
    }

    public static function smsApiTokenMissing(): self
    {
        return new self('SMSAPI token is missing. Please set config("smsapi.token").');
    }

    public static function smsApiRecipientMissing(): self
    {
        return new self('SMSAPI recipient is missing. Add SmsApiMessage::to(), SmsApiMessage::toMany() or define routeNotificationForSmsApi() on the notifiable model.');
    }

    public static function unsupportedService(string $service): self
    {
        return new self(sprintf('Unsupported SMSAPI service `%s`. Allowed values: `pl`, `com`.', $service));
    }

    public static function unsupportedMessageType(string $type): self
    {
        return new self(sprintf('Unsupported SMSAPI message type `%s`. Allowed values: `sms`, `mms`.', $type));
    }

    public static function mmsNotSupportedForService(string $service): self
    {
        return new self(sprintf('MMS is only supported for the `pl` SMSAPI service. Current service: `%s`.', $service));
    }

    public static function smsApiMmsPayloadIncomplete(): self
    {
        return new self('MMS payload is incomplete. Call SmsApiMessage::mms($subject, $smil) before sending.');
    }

    public static function bulkMmsNotSupported(): self
    {
        return new self('Bulk sending is supported only for SMS messages. MMS requires a single recipient.');
    }
}
