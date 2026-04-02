<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Dto;

final readonly class SmsApiRequest
{
    public function __construct(
        public string $type = 'sms',
        public string $message = '',
        public string|array|null $to = null,
        public ?string $from = null,
        public ?string $subject = null,
        public ?string $smil = null,
        public array $attributes = [],
    ) {}

    public function withRecipient(string|array $recipient): self
    {
        return new self(
            type: $this->type,
            message: $this->message,
            to: $recipient,
            from: $this->from,
            subject: $this->subject,
            smil: $this->smil,
            attributes: $this->attributes,
        );
    }

    public function withDefaultFrom(?string $defaultFrom): self
    {
        return new self(
            type: $this->type,
            message: $this->message,
            to: $this->to,
            from: $this->from ?? $defaultFrom,
            subject: $this->subject,
            smil: $this->smil,
            attributes: $this->attributes,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type !== 'sms' ? $this->type : null,
            'message' => $this->message !== '' ? $this->message : null,
            'subject' => $this->subject,
            'smil' => $this->smil,
            ...$this->attributes,
            'from' => $this->from,
            'to' => $this->to,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
