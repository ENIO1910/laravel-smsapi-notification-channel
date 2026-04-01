<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\SmsApi\Contracts\SmsApi as SmsApiContract;
use Smsapi\Client\SmsapiClient as OfficialSmsapiClient;
use Smsapi\Client\SmsapiHttpClient as OfficialSmsapiHttpClient;

final class SmsApiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/smsapi.php' => config_path('smsapi.php'),
        ], 'smsapi-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smsapi.php', 'smsapi');

        $this->app->bind(OfficialSmsapiClient::class, function (): OfficialSmsapiClient {
            $timeout = (int) config('smsapi.timeout', 10);

            return new OfficialSmsapiHttpClient(
                new HttpClient(['timeout' => $timeout]),
                new HttpFactory(),
                new HttpFactory(),
            );
        });

        $this->app->bind(SmsApiContract::class, fn (Container $app): SmsApi => new SmsApi($app->make(OfficialSmsapiClient::class)));

        Notification::extend('smsApi', fn (Container $app): SmsApiChannel => $app->make(SmsApiChannel::class));
    }
}
