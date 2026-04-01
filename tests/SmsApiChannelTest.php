<?php

use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\SmsApi;
use NotificationChannels\SmsApi\SmsApiChannel;
use NotificationChannels\SmsApi\SmsApiMessage;

beforeEach(function () {
    $this->smsApi = Mockery::mock(SmsApi::class);
});

it('can send a notification', function () {
    $message = SmsApiMessage::create('Test message');

    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(function (SmsApiMessage $sentMessage): bool {
            return $sentMessage->toArray() === [
                'message' => 'Test message',
                'to' => '+48123123123',
            ];
        }))
        ->andReturn(new SmsApiResponse(200, [], '{"ok":true}', ['ok' => true]));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiable, new TestNotification);

    expect($response)->toBeInstanceOf(SmsApiResponse::class)
        ->and($response->statusCode)->toEqual(200)
        ->and($response->successful())->toBeTrue()
        ->and($response->decoded)->toBe(['ok' => true]);
});

it('returns null when notification does not return a SmsApiMessage', function () {
    $this->smsApi
        ->shouldNotReceive('send');

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiable, new InvalidTestNotification);

    expect($response)->toBeNull();
});

it('can use explicit recipient from message', function () {
    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(function (SmsApiMessage $sentMessage): bool {
            return $sentMessage->toArray()['to'] === '+48999111222';
        }))
        ->andReturn(new SmsApiResponse(200));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiableWithoutRoute, new TestNotificationWithToParam);

    expect($response->statusCode)->toEqual(200);
});

class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForSmsApi(?Notification $notification = null): string
    {
        return '+48123123123';
    }
}

class TestNotifiableWithoutRoute
{
    use Notifiable;
}

class TestNotification extends Notification
{
    public function toSmsApi($notifiable): SmsApiMessage
    {
        return SmsApiMessage::create('Test message');
    }
}

class InvalidTestNotification extends Notification
{
    public function toSmsApi($notifiable): array
    {
        return [];
    }
}

class TestNotificationWithToParam extends Notification
{
    public function toSmsApi($notifiable): SmsApiMessage
    {
        return SmsApiMessage::create('Test message')
            ->to('+48999111222');
    }
}
