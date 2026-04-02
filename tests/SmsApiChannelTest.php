<?php

declare(strict_types=1);

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;
use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\SmsApiChannel;
use NotificationChannels\SmsApi\SmsApiMessage;

beforeEach(function (): void {
    $this->smsApi = Mockery::mock(SmsApiContract::class);
});

it('can send a notification', function (): void {
    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (SmsApiMessage $sentMessage): bool => $sentMessage->toArray() === [
            'message' => 'Test message',
            'to' => '+48123123123',
        ]))
        ->andReturn(new SmsApiResponse(200, [], '{"ok":true}', ['ok' => true]));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiable, new TestNotification);

    expect($response)->toBeInstanceOf(SmsApiResponse::class)
        ->and($response->statusCode)->toEqual(200)
        ->and($response->successful())->toBeTrue()
        ->and($response->decoded)->toBe(['ok' => true]);
});

it('returns null when notification does not return a SmsApiMessage', function (): void {
    $this->smsApi
        ->shouldNotReceive('send');

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiable, new InvalidTestNotification);

    expect($response)->toBeNull();
});

it('can use explicit recipient from message', function (): void {
    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (SmsApiMessage $sentMessage): bool => $sentMessage->toArray()['to'] === '+48999111222'))
        ->andReturn(new SmsApiResponse(200));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiableWithoutRoute, new TestNotificationWithToParam);

    expect($response->statusCode)->toEqual(200);
});

it('can use on-demand recipient routed by channel class', function (): void {
    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (SmsApiMessage $sentMessage): bool => $sentMessage->toArray()['to'] === '+48777111222'))
        ->andReturn(new SmsApiResponse(200));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(
        (new AnonymousNotifiable)->route(SmsApiChannel::class, '+48777111222'),
        new TestNotification,
    );

    expect($response->statusCode)->toEqual(200);
});

it('can use multiple recipients from notifiable route', function (): void {
    $this->smsApi
        ->shouldReceive('send')
        ->once()
        ->with(Mockery::on(fn (SmsApiMessage $sentMessage): bool => $sentMessage->toArray()['to'] === [
            '+48123123123',
            '+48999111222',
        ]))
        ->andReturn(new SmsApiResponse(200));

    $channel = new SmsApiChannel($this->smsApi);
    $response = $channel->send(new TestNotifiableWithBulkRoute, new TestNotification);

    expect($response->statusCode)->toEqual(200);
});

final class TestNotifiable
{
    use Notifiable;

    public function routeNotificationForSmsApi(): string
    {
        return '+48123123123';
    }
}

final class TestNotifiableWithoutRoute
{
    use Notifiable;
}

final class TestNotifiableWithBulkRoute
{
    use Notifiable;

    public function routeNotificationForSmsApi(): array
    {
        return ['+48123123123', '+48999111222'];
    }
}

final class TestNotification extends Notification
{
    public function toSmsApi($notifiable): SmsApiMessage
    {
        return SmsApiMessage::create('Test message');
    }
}

final class InvalidTestNotification extends Notification
{
    public function toSmsApi($notifiable): array
    {
        return [];
    }
}

final class TestNotificationWithToParam extends Notification
{
    public function toSmsApi($notifiable): SmsApiMessage
    {
        return SmsApiMessage::create('Test message')
            ->to('+48999111222');
    }
}
