<?php

declare(strict_types=1);

use Illuminate\Container\Container;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\Exceptions\CouldNotSendNotification;
use NotificationChannels\SmsApi\SmsApi;
use NotificationChannels\SmsApi\SmsApiMessage;
use Smsapi\Client\Feature\Mms\Data\Mms;
use Smsapi\Client\Feature\Mms\MmsFeature;
use Smsapi\Client\Feature\Sms\Data\Sms;
use Smsapi\Client\Feature\Sms\SmsFeature;
use Smsapi\Client\Service\SmsapiComService;
use Smsapi\Client\Service\SmsapiPlService;
use Smsapi\Client\SmsapiClient;

beforeEach(function (): void {
    $this->errorReporting = error_reporting();
    error_reporting($this->errorReporting & ~E_DEPRECATED);

    $container = new Container();
    $container->instance('config', new class
    {
        public function __construct(
            private array $items = [
                'smsapi' => [
                    'service' => 'pl',
                    'uri' => null,
                    'token' => 'test-token',
                    'from' => 'Laravel13',
                    'timeout' => 5,
                ],
            ],
        ) {}

        public function get(string $key, mixed $default = null): mixed
        {
            $segments = explode('.', $key);
            $value = $this->items;

            foreach ($segments as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    return $default;
                }

                $value = $value[$segment];
            }

            return $value;
        }

        public function set(string $key, mixed $value): void
        {
            $segments = explode('.', $key);
            $items = &$this->items;

            foreach ($segments as $segment) {
                if (! isset($items[$segment]) || ! is_array($items[$segment])) {
                    $items[$segment] = [];
                }

                $items = &$items[$segment];
            }

            $items = $value;
        }
    });

    Container::setInstance($container);
});

afterEach(function (): void {
    error_reporting($this->errorReporting);
    Container::setInstance();
});

it('uses smsapi pl service and maps response data', function (): void {
    $smsFeature = Mockery::mock(SmsFeature::class);
    $service = Mockery::mock(SmsapiPlService::class);
    $client = Mockery::mock(SmsapiClient::class);

    $sms = new Sms();
    $sms->id = 'sms-1';
    $sms->points = 0.5;
    $sms->number = '+48123123123';
    $sms->status = 'QUEUE';

    $client->shouldReceive('smsapiPlService')
        ->once()
        ->with('test-token')
        ->andReturn($service);

    $service->shouldReceive('smsFeature')
        ->once()
        ->andReturn($smsFeature);

    $smsFeature->shouldReceive('sendSms')
        ->once()
        ->with(Mockery::on(fn ($bag): bool => $bag->to === '+48123123123'
            && $bag->message === 'Test message'
            && $bag->from === 'Laravel13'
            && $bag->encoding === 'utf-8'))
        ->andReturn($sms);

    $response = (new SmsApi($client))->send(
        SmsApiMessage::create('Test message')->to('+48123123123')
    );

    expect($response)->toBeInstanceOf(SmsApiResponse::class)
        ->and($response->decoded)->toBe([
            'id' => 'sms-1',
            'points' => 0.5,
            'number' => '+48123123123',
            'status' => 'QUEUE',
        ]);
});

it('uses smsapi com service with custom uri when configured', function (): void {
    config()->set('smsapi.service', 'com');
    config()->set('smsapi.uri', 'https://custom.smsapi.test');

    $smsFeature = Mockery::mock(SmsFeature::class);
    $service = Mockery::mock(SmsapiComService::class);
    $client = Mockery::mock(SmsapiClient::class);

    $sms = new Sms();
    $sms->id = 'sms-2';
    $sms->points = 1.0;
    $sms->number = '+48999111222';
    $sms->status = 'OK';

    $client->shouldReceive('smsapiComServiceWithUri')
        ->once()
        ->with('test-token', 'https://custom.smsapi.test')
        ->andReturn($service);

    $service->shouldReceive('smsFeature')
        ->once()
        ->andReturn($smsFeature);

    $smsFeature->shouldReceive('sendSms')
        ->once()
        ->with(Mockery::on(fn ($bag): bool => $bag->to === '+48999111222'
            && $bag->message === 'Test message'
            && $bag->from === 'SenderX'
            && $bag->test === true))
        ->andReturn($sms);

    $response = (new SmsApi($client))->send(
        SmsApiMessage::create('Test message')
            ->to('+48999111222')
            ->from('SenderX')
            ->set('test', true)
    );

    expect($response->decoded['id'])->toBe('sms-2')
        ->and($response->decoded['status'])->toBe('OK');
});

it('throws for unsupported service', function (): void {
    config()->set('smsapi.service', 'de');

    expect(fn (): SmsApiResponse => (new SmsApi(Mockery::mock(SmsapiClient::class)))->send(
        SmsApiMessage::create('Test message')->to('+48123123123')
    ))->toThrow(CouldNotSendNotification::class, 'Unsupported SMSAPI service');
});

it('can send an mms through smsapi pl service', function (): void {
    $mmsFeature = Mockery::mock(MmsFeature::class);
    $service = Mockery::mock(SmsapiPlService::class);
    $client = Mockery::mock(SmsapiClient::class);

    $mms = new Mms();
    $mms->id = 'mms-1';
    $mms->points = 2.5;
    $mms->number = '+48123123123';
    $mms->status = 'QUEUE';

    $client->shouldReceive('smsapiPlService')
        ->once()
        ->with('test-token')
        ->andReturn($service);

    $service->shouldReceive('mmsFeature')
        ->once()
        ->andReturn($mmsFeature);

    $mmsFeature->shouldReceive('sendMms')
        ->once()
        ->with(Mockery::on(fn ($bag): bool => $bag->to === '+48123123123'
            && $bag->subject === 'Invoice'
            && $bag->smil === '<smil/>'
            && $bag->test === true))
        ->andReturn($mms);

    $response = (new SmsApi($client))->send(
        SmsApiMessage::create()
            ->to('+48123123123')
            ->mms('Invoice', '<smil/>')
            ->set('test', true)
    );

    expect($response)->toBeInstanceOf(SmsApiResponse::class)
        ->and($response->decoded)->toBe([
            'id' => 'mms-1',
            'points' => 2.5,
            'number' => '+48123123123',
            'status' => 'QUEUE',
        ]);
});

it('throws when mms is used with smsapi com service', function (): void {
    config()->set('smsapi.service', 'com');

    expect(fn (): SmsApiResponse => (new SmsApi(Mockery::mock(SmsapiClient::class)))->send(
        SmsApiMessage::create()
            ->to('+48123123123')
            ->mms('Invoice', '<smil/>')
    ))->toThrow(CouldNotSendNotification::class, 'MMS is only supported for the `pl` SMSAPI service');
});
