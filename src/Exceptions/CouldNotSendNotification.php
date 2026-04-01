<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Exceptions;

use Exception;
use GuzzleHttp\Exception\ClientException;

final class CouldNotSendNotification extends Exception
{
    public static function smsApiRespondedWithAnError(ClientException $exception): self
    {
        if (! $exception->hasResponse()) {
            return new self('SMSAPI responded with an error but no response body was found.');
        }

        $statusCode = $exception->getResponse()->getStatusCode();
        $description = $exception->getMessage();

        return new self(sprintf('SMSAPI responded with an error `%d - %s`.', $statusCode, $description));
    }

    public static function couldNotCommunicateWithSmsApi(Exception $exception): self
    {
        return new self(sprintf('The communication with SMSAPI failed. `%s`.', $exception->getMessage()));
    }

    public static function smsApiTokenMissing(): self
    {
        return new self('SMSAPI token is missing. Please set config("services.smsapi.token").');
    }

    public static function smsApiRecipientMissing(): self
    {
        return new self('SMSAPI recipient is missing. Add SmsApiMessage::to() or define routeNotificationForSmsApi() on the notifiable model.');
    }
}
