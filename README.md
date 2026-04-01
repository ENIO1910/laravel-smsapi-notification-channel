# SMSAPI Notification Channel for Laravel

This package makes it easy to send notifications using SMSAPI with Laravel 13.

## Installation

```bash
composer require notification-channels/smsapi
```

If you are using Laravel without package auto-discovery, add the service provider to `config/app.php`:

```php
'providers' => [
    NotificationChannels\SmsApi\SmsApiServiceProvider::class,
],
```

Publish the config:

```bash
php artisan vendor:publish --tag=smsapi-config
```

## Configuration

Configure `config/smsapi.php`:

```php
return [
    'base_url' => env('SMSAPI_BASE_URL', 'https://api.smsapi.example'),
    'token' => env('SMSAPI_TOKEN'),
    'from' => env('SMSAPI_FROM'),
    'timeout' => (int) env('SMSAPI_TIMEOUT', 10),
];
```

Or set only `.env` values:

```env
SMSAPI_BASE_URL=https://api.smsapi.example
SMSAPI_TOKEN=your-token
SMSAPI_FROM=YourBrand
SMSAPI_TIMEOUT=10
```

## Usage

```php
use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\SmsApiChannel;
use NotificationChannels\SmsApi\SmsApiMessage;

class InvoicePaid extends Notification
{
    public function via(object $notifiable): array
    {
        return [SmsApiChannel::class];
    }

    public function toSmsApi(object $notifiable): SmsApiMessage
    {
        return SmsApiMessage::create('Faktura została opłacona.');
    }
}
```

Instead of adding `to()` to the message, you can define the recipient on the notifiable model:

```php
public function routeNotificationForSmsApi(?Notification $notification = null): string
{
    return $this->phone;
}
```

Or provide it directly in the message:

```php
return SmsApiMessage::create('Faktura została opłacona.')
    ->to('+48123123123')
    ->from('MyBrand');
```
