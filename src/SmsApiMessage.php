<?php

namespace NotificationChannels\SmsApi;

use NotificationChannels\SmsApi\Dto\SmsApiRequest;

class SmsApiMessage
{
    protected ?string $recipient = null;

    protected ?string $from = null;

    protected array $payload = [];

    public static function create(string $message = ''): self
    {
        return new self($message);
    }

    public function __construct(string $message = '')
    {
        $this->payload = [
            'message' => $message,
        ];
    }

    public function to(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function from(string $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function message(string $message): self
    {
        $this->payload['message'] = $message;

        return $this;
    }

    public function set(string $key, mixed $value): self
    {
        $this->payload[$key] = $value;

        return $this;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function recipientNotGiven(): bool
    {
        return ! $this->recipient;
    }

    public function toArray(?string $defaultFrom = null): array
    {
        return $this->toDto($defaultFrom)->toArray();
    }

    public function toDto(?string $defaultFrom = null): SmsApiRequest
    {
        return new SmsApiRequest(
            message: (string) ($this->payload['message'] ?? ''),
            to: $this->recipient,
            from: $this->from ?? $defaultFrom,
            attributes: array_diff_key($this->payload, ['message' => true]),
        );
    }
}
