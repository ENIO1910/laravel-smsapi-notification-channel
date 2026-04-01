<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;

final class SmsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/smsapi.php' => config_path('smsapi.php'),
        ], 'smsapi-config');

        $this->app->when(SmsApiChannel::class)
            ->needs(SmsApiContract::class)
            ->give(fn (): SmsApi => new SmsApi(new HttpClient));
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smsapi.php', 'smsapi');

        $this->app->bind(SmsApiContract::class, fn (): SmsApi => new SmsApi(new HttpClient));

        Notification::extend('smsApi', fn (Container $app): SmsApiChannel => $app->make(SmsApiChannel::class));
    }
}
