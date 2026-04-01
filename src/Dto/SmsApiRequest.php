<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Dto;

final readonly class SmsApiRequest
{
    public function __construct(
        public string $message,
        public ?string $to = null,
        public ?string $from = null,
        public array $attributes = [],
    ) {}

    public function withRecipient(string $recipient): self
    {
        return new self(
            message: $this->message,
            to: $recipient,
            from: $this->from,
            attributes: $this->attributes,
        );
    }

    public function withDefaultFrom(?string $defaultFrom): self
    {
        return new self(
            message: $this->message,
            to: $this->to,
            from: $this->from ?? $defaultFrom,
            attributes: $this->attributes,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'message' => $this->message,
            ...$this->attributes,
            'from' => $this->from,
            'to' => $this->to,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
