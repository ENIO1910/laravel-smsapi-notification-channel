<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Dto;

use Psr\Http\Message\ResponseInterface;
use Smsapi\Client\Feature\Mms\Data\Mms;
use Smsapi\Client\Feature\Sms\Data\Sms;

final readonly class SmsApiResponse
{
    public function __construct(
        public int $statusCode,
        public array $headers = [],
        public ?string $body = null,
        public array $decoded = [],
    ) {}

    public static function fromPsrResponse(ResponseInterface $response): self
    {
        $body = (string) $response->getBody();
        $decoded = json_decode($body, true);

        return new self(
            statusCode: $response->getStatusCode(),
            headers: $response->getHeaders(),
            body: $body,
            decoded: is_array($decoded) ? $decoded : [],
        );
    }

    public static function fromSmsData(Sms|Mms $sms): self
    {
        return new self(
            statusCode: 200,
            decoded: self::normalizeSmsData($sms),
        );
    }

    public static function fromSmsList(array $messages): self
    {
        return new self(
            statusCode: 200,
            decoded: [
                'count' => count($messages),
                'results' => array_map(
                    self::normalizeSmsData(...),
                    $messages,
                ),
            ],
        );
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    private static function normalizeSmsData(Sms|Mms $sms): array
    {
        return array_filter([
            'id' => $sms->id ?? null,
            'points' => $sms->points ?? null,
            'number' => $sms->number ?? null,
            'status' => $sms->status ?? null,
            'idx' => $sms->idx ?? null,
            'date_sent' => isset($sms->dateSent) ? $sms->dateSent->format(DATE_ATOM) : null,
            'content' => isset($sms->content) ? get_object_vars($sms->content) : null,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
