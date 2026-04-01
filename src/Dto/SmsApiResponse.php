<?php

namespace NotificationChannels\SmsApi\Dto;

use Psr\Http\Message\ResponseInterface;

final class SmsApiResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers = [],
        public readonly ?string $body = null,
        public readonly array $decoded = [],
    ) {
    }

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

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
