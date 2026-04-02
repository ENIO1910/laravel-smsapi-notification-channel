<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;
use NotificationChannels\SmsApi\Dto\SmsApiRequest;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\Exceptions\CouldNotSendNotification;
use Smsapi\Client\Feature\Mms\Bag\SendMmsBag;
use Smsapi\Client\Feature\Mms\Data\Mms;
use Smsapi\Client\SmsapiClient as OfficialSmsapiClient;
use Smsapi\Client\SmsapiClientException;
use Smsapi\Client\SmsapiHttpClient as OfficialSmsapiHttpClient;

final readonly class SmsApi implements SmsApiContract
{
    public function __construct(private ?OfficialSmsapiClient $client = null) {}

    public function send(SmsApiMessage $message): SmsApiResponse
    {
        $config = config('smsapi', []);
        $token = $config['token'] ?? null;
        $request = $message
            ->toDto()
            ->withDefaultFrom($config['from'] ?? null);

        if (! $token) {
            throw CouldNotSendNotification::smsApiTokenMissing();
        }

        if ($request->to === null || $request->to === []) {
            throw CouldNotSendNotification::smsApiRecipientMissing();
        }

        return $this->sendRequest(
            $request,
            (string) $token,
            mb_strtolower((string) ($config['service'] ?? 'pl')),
            $config['uri'] ? (string) $config['uri'] : null,
            (int) ($config['timeout'] ?? 10),
        );
    }

    private function sendRequest(
        SmsApiRequest $request,
        string $token,
        string $service,
        ?string $uri,
        int $timeout,
    ): SmsApiResponse {
        try {
            if ($request->type === 'mms' && is_array($request->to)) {
                throw CouldNotSendNotification::bulkMmsNotSupported();
            }

            $client = $this->client ?? new OfficialSmsapiHttpClient(
                new Client(['timeout' => $timeout]),
                new HttpFactory(),
                new HttpFactory(),
            );

            if ($request->type === 'mms' && $service !== 'pl') {
                throw CouldNotSendNotification::mmsNotSupportedForService($service);
            }

            $serviceClient = match ($service) {
                'pl' => $uri ? $client->smsapiPlServiceWithUri($token, $uri) : $client->smsapiPlService($token),
                'com' => $uri ? $client->smsapiComServiceWithUri($token, $uri) : $client->smsapiComService($token),
                default => throw CouldNotSendNotification::unsupportedService($service),
            };

            return match ($request->type) {
                'sms' => is_array($request->to)
                    ? SmsApiResponse::fromSmsList(
                        $serviceClient->smsFeature()->sendSmss($this->createSmssBag($request))
                    )
                    : SmsApiResponse::fromSmsData(
                        $serviceClient->smsFeature()->sendSms($this->createSmsBag($request))
                    ),
                'mms' => SmsApiResponse::fromSmsData(
                    $this->sendMms($serviceClient, $request)
                ),
                default => throw CouldNotSendNotification::unsupportedMessageType($request->type),
            };
        } catch (SmsapiClientException $exception) {
            throw CouldNotSendNotification::smsApiRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithSmsApi($exception);
        }
    }

    private function createSmsBag(SmsApiRequest $request): object
    {
        $class = $this->loadClassSuppressingDeprecations('Smsapi\\Client\\Feature\\Sms\\Bag\\SendSmsBag');
        $sms = $class::withMessage($request->to, $request->message);

        if ($request->from !== null) {
            $sms->from = $request->from;
        }

        foreach ($request->attributes as $key => $value) {
            $sms->{$key} = $value;
        }

        return $sms;
    }

    private function createSmssBag(SmsApiRequest $request): object
    {
        $class = $this->loadClassSuppressingDeprecations('Smsapi\\Client\\Feature\\Sms\\Bag\\SendSmssBag');
        $sms = $class::withMessage($request->to, $request->message);

        if ($request->from !== null) {
            $sms->from = $request->from;
        }

        foreach ($request->attributes as $key => $value) {
            $sms->{$key} = $value;
        }

        return $sms;
    }

    private function sendMms(object $serviceClient, SmsApiRequest $request): Mms
    {
        if ($request->subject === null || $request->smil === null) {
            throw CouldNotSendNotification::smsApiMmsPayloadIncomplete();
        }

        $mms = new SendMmsBag($request->to, $request->subject, $request->smil);

        foreach ($request->attributes as $key => $value) {
            $mms->{$key} = $value;
        }

        return $serviceClient->mmsFeature()->sendMms($mms);
    }

    private function loadClassSuppressingDeprecations(string $class): string
    {
        set_error_handler(static function (int $errno): bool {
            return $errno === E_DEPRECATED;
        });

        try {
            class_exists($class);
        } finally {
            restore_error_handler();
        }

        return $class;
    }
}
