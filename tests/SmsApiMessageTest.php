<?php

use NotificationChannels\SmsApi\Dto\SmsApiRequest;
use NotificationChannels\SmsApi\SmsApiMessage;

it('can build a payload', function () {
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

it('uses default from when message from is not set', function () {
    $payload = SmsApiMessage::create('Example')
        ->to('+48123123123')
        ->toArray('DefaultSender');

    expect($payload)->toEqual([
        'message' => 'Example',
        'from' => 'DefaultSender',
        'to' => '+48123123123',
    ]);
});

it('can convert a message to dto', function () {
    $dto = SmsApiMessage::create('Example')
        ->to('+48123123123')
        ->from('Laravel13')
        ->set('encoding', 'utf-8')
        ->toDto();

    expect($dto)->toBeInstanceOf(SmsApiRequest::class)
        ->and($dto->message)->toBe('Example')
        ->and($dto->to)->toBe('+48123123123')
        ->and($dto->from)->toBe('Laravel13')
        ->and($dto->attributes)->toBe(['encoding' => 'utf-8']);
});
