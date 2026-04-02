<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Response;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use Smsapi\Client\Feature\Mms\Data\Mms;
use Smsapi\Client\Feature\Sms\Data\Sms;

it('can build response dto from psr response', function (): void {
    $response = new Response(
        200,
        ['Content-Type' => ['application/json']],
        json_encode(['id' => '123', 'count' => 1], JSON_THROW_ON_ERROR)
    );

    $dto = SmsApiResponse::fromPsrResponse($response);

    expect($dto->statusCode)->toBe(200)
        ->and($dto->successful())->toBeTrue()
        ->and($dto->headers)->toHaveKey('Content-Type')
        ->and($dto->decoded)->toBe(['id' => '123', 'count' => 1]);
});

it('keeps empty decoded payload for non json body', function (): void {
    $response = new Response(202, [], 'accepted');

    $dto = SmsApiResponse::fromPsrResponse($response);

    expect($dto->body)->toBe('accepted')
        ->and($dto->decoded)->toBe([])
        ->and($dto->successful())->toBeTrue();
});

it('can build response dto from smsapi sms data', function (): void {
    $sms = new Sms();
    $sms->id = 'abc123';
    $sms->points = 0.5;
    $sms->number = '+48123123123';
    $sms->status = 'QUEUE';
    $sms->idx = 'ext-1';
    $sms->dateSent = new DateTimeImmutable('2026-04-01T10:00:00+00:00');

    $dto = SmsApiResponse::fromSmsData($sms);

    expect($dto->statusCode)->toBe(200)
        ->and($dto->successful())->toBeTrue()
        ->and($dto->decoded)->toBe([
            'id' => 'abc123',
            'points' => 0.5,
            'number' => '+48123123123',
            'status' => 'QUEUE',
            'idx' => 'ext-1',
            'date_sent' => '2026-04-01T10:00:00+00:00',
        ]);
});

it('can build response dto from smsapi mms data', function (): void {
    $mms = new Mms();
    $mms->id = 'mms123';
    $mms->points = 2.5;
    $mms->number = '+48123123123';
    $mms->status = 'QUEUE';
    $mms->dateSent = new DateTimeImmutable('2026-04-01T10:00:00+00:00');

    $dto = SmsApiResponse::fromSmsData($mms);

    expect($dto->statusCode)->toBe(200)
        ->and($dto->successful())->toBeTrue()
        ->and($dto->decoded)->toBe([
            'id' => 'mms123',
            'points' => 2.5,
            'number' => '+48123123123',
            'status' => 'QUEUE',
            'date_sent' => '2026-04-01T10:00:00+00:00',
        ]);
});
