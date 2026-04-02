<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use NotificationChannels\SmsApi\Dto\SmsApiRequest;

final class SmsApiMessage
{
    private string $type = 'sms';

    private ?string $recipient = null;

    private ?string $from = null;

    private ?string $subject = null;

    private ?string $smil = null;

    private array $payload = [];

    public function __construct(string $message = '')
    {
        $this->payload = [
            'message' => $message,
        ];
    }

    public static function create(string $message = ''): self
    {
        return new self($message);
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

    public function mms(string $subject, string $smil): self
    {
        $this->type = 'mms';
        $this->subject = $subject;
        $this->smil = $smil;

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
            type: $this->type,
            message: (string) ($this->payload['message'] ?? ''),
            to: $this->recipient,
            from: $this->from ?? $defaultFrom,
            subject: $this->subject,
            smil: $this->smil,
            attributes: array_diff_key($this->payload, ['message' => true]),
        );
    }
}
