<?php

use GuzzleHttp\Psr7\Response;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;

it('can build response dto from psr response', function () {
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

it('keeps empty decoded payload for non json body', function () {
    $response = new Response(202, [], 'accepted');

    $dto = SmsApiResponse::fromPsrResponse($response);

    expect($dto->body)->toBe('accepted')
        ->and($dto->decoded)->toBe([])
        ->and($dto->successful())->toBeTrue();
});
