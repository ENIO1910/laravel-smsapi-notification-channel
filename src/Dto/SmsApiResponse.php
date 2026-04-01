<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Dto;

use Psr\Http\Message\ResponseInterface;

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

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
