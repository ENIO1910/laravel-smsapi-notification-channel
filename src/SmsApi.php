<?php

namespace NotificationChannels\SmsApi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use NotificationChannels\SmsApi\Dto\SmsApiRequest;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\Exceptions\CouldNotSendNotification;

class SmsApi
{
    public function __construct(protected Client $httpClient)
    {
    }

    public function send(SmsApiMessage $message): SmsApiResponse
    {
        $config = config('smsapi', []);
        $token = $config['token'] ?? null;
        $baseUrl = rtrim((string) ($config['base_url'] ?? 'https://api.smsapi.example'), '/');
        $timeout = (int) ($config['timeout'] ?? 10);
        $request = $message
            ->toDto()
            ->withDefaultFrom($config['from'] ?? null);

        if (! $token) {
            throw CouldNotSendNotification::smsApiTokenMissing();
        }

        if (! $request->to) {
            throw CouldNotSendNotification::smsApiRecipientMissing();
        }

        return $this->sendRequest($request, $baseUrl, (string) $token, $timeout);
    }

    protected function sendRequest(SmsApiRequest $request, string $baseUrl, string $token, int $timeout): SmsApiResponse
    {
        try {
            $response = $this->httpClient->post($baseUrl . '/sms', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $request->toArray(),
                'timeout' => $timeout,
            ]);

            return SmsApiResponse::fromPsrResponse($response);
        } catch (ClientException $exception) {
            throw CouldNotSendNotification::smsApiRespondedWithAnError($exception);
        } catch (Exception $exception) {
            throw CouldNotSendNotification::couldNotCommunicateWithSmsApi($exception);
        }
    }
}
