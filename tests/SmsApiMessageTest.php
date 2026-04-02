<?php

declare(strict_types=1);

use NotificationChannels\SmsApi\Dto\SmsApiRequest;
use NotificationChannels\SmsApi\SmsApiMessage;

it('can build a payload', function (): void {
    $payload = SmsApiMessage::create('Example')
        ->to('+48123123123')
        ->from('Laravel13')
        ->set('test', true)
        ->toArray();

    expect($payload)->toEqual([
        'message' => 'Example',
        'test' => true,
        'from' => 'Laravel13',
        'to' => '+48123123123',
    ]);
});

it('uses default from when message from is not set', function (): void {
    $payload = SmsApiMessage::create('Example')
        ->to('+48123123123')
        ->toArray('DefaultSender');

    expect($payload)->toEqual([
        'message' => 'Example',
        'from' => 'DefaultSender',
        'to' => '+48123123123',
    ]);
});

it('can convert a message to dto', function (): void {
    $dto = SmsApiMessage::create('Example')
        ->to('+48123123123')
        ->from('Laravel13')
        ->set('encoding', 'utf-8')
        ->toDto();

    expect($dto)->toBeInstanceOf(SmsApiRequest::class)
        ->and($dto->type)->toBe('sms')
        ->and($dto->message)->toBe('Example')
        ->and($dto->to)->toBe('+48123123123')
        ->and($dto->from)->toBe('Laravel13')
        ->and($dto->attributes)->toBe(['encoding' => 'utf-8']);
});

it('can build an mms payload', function (): void {
    $payload = SmsApiMessage::create()
        ->to('+48123123123')
        ->mms('Subject', '<smil/>')
        ->set('test', true)
        ->toArray();

    expect($payload)->toEqual([
        'type' => 'mms',
        'subject' => 'Subject',
        'smil' => '<smil/>',
        'test' => true,
        'to' => '+48123123123',
    ]);
});

it('can convert an mms message to dto', function (): void {
    $dto = SmsApiMessage::create()
        ->to('+48123123123')
        ->mms('Subject', '<smil/>')
        ->set('test', true)
        ->toDto();

    expect($dto)->toBeInstanceOf(SmsApiRequest::class)
        ->and($dto->type)->toBe('mms')
        ->and($dto->message)->toBe('')
        ->and($dto->to)->toBe('+48123123123')
        ->and($dto->subject)->toBe('Subject')
        ->and($dto->smil)->toBe('<smil/>')
        ->and($dto->attributes)->toBe(['test' => true]);
});
