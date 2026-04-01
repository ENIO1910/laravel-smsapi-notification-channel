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
use Smsapi\Client\Feature\Sms\Bag\SendSmsBag;
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

        if (! $request->to) {
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
            $client = $this->client ?? new OfficialSmsapiHttpClient(
                new Client(['timeout' => $timeout]),
                new HttpFactory(),
                new HttpFactory(),
            );

            $sms = SendSmsBag::withMessage($request->to, $request->message);

            if ($request->from !== null) {
                $sms->from = $request->from;
            }

            foreach ($request->attributes as $key => $value) {
                $sms->{$key} = $value;
            }

            $serviceClient = match ($service) {
                'pl' => $uri ? $client->smsapiPlServiceWithUri($token, $uri) : $client->smsapiPlService($token),
                'com' => $uri ? $client->smsapiComServiceWithUri($token, $uri) : $client->smsapiComService($token),
                default => throw CouldNotSendNotification::unsupportedService($service),
            };

            return SmsApiResponse::fromSmsData(
                $serviceClient->smsFeature()->sendSms($sms)
            );
        } catch (SmsapiClientException $exception) {
            throw CouldNotSendNotification::smsApiRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithSmsApi($exception);
        }
    }
}
