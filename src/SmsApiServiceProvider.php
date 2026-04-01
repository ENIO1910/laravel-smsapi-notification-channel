<?php

namespace NotificationChannels\SmsApi;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class SmsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/smsapi.php' => config_path('smsapi.php'),
        ], 'smsapi-config');

        $this->app->when(SmsApiChannel::class)
            ->needs(SmsApi::class)
            ->give(function (): SmsApi {
                return new SmsApi(new HttpClient);
            });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/smsapi.php', 'smsapi');

        Notification::extend('smsapi', function (Container $app): SmsApiChannel {
            return $app->make(SmsApiChannel::class);
        });
    }
}
