# SMSAPI Notification Channel for Laravel

This package makes it easy to send SMS and MMS notifications using SMSAPI with Laravel 11, 12, and 13.

## Installation

```bash
composer require patrykstaniewski/laravel-smsapi-notification-channel
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
    'service' => env('SMSAPI_SERVICE', 'pl'),
    'uri' => env('SMSAPI_URI'),
    'token' => env('SMSAPI_TOKEN'),
    'from' => env('SMSAPI_FROM'),
    'timeout' => (int) env('SMSAPI_TIMEOUT', 10),
];
```

Or set only `.env` values:

```env
SMSAPI_SERVICE=pl
SMSAPI_URI=
SMSAPI_TOKEN=your-token
SMSAPI_FROM=YourBrand
SMSAPI_TIMEOUT=10
```

The package uses the official `smsapi/php-client` v3 adapter internally.

- `service=pl` uses `smsapiPlService()`
- `service=com` uses `smsapiComService()`
- if `uri` is set, the package uses the matching `*ServiceWithUri()` variant
- MMS sending is available only for `service=pl`, because the official SMSAPI client exposes `mmsFeature()` only there

## Response DTO

The channel returns `NotificationChannels\SmsApi\Dto\SmsApiResponse`.

For real SMSAPI sends the package maps the response returned by `smsapi/php-client` and normalizes it to:

- `statusCode`: local adapter status, currently `200` when the SMSAPI client accepted the send request
- `decoded.id`: SMS/MMS identifier
- `decoded.points`: charged points
- `decoded.number`: recipient number
- `decoded.status`: SMS/MMS status returned by SMSAPI
- `decoded.idx`: external identifier if present
- `decoded.date_sent`: ISO-8601 sent date if available

This is adapter-level data, not the raw HTTP response from SMSAPI.

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

To send the notification, call `notify()` on your model:

```php
$user->notify(new InvoicePaid());
```

The model you call `notify()` on must use the `Illuminate\Notifications\Notifiable` trait.

Example:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
}
```

If you do not pass the recipient directly with `to()`, you must define the recipient on the notifiable model:

```php
use Illuminate\Notifications\Notification;

public function routeNotificationForSmsApi(?Notification $notification = null): string
{
    return $this->phone;
}
```

This method name is intentional: the package uses the `smsApi` channel name so Laravel resolves `routeNotificationForSmsApi()`.

In practice, a complete notifiable model can look like this:

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class User extends Authenticatable
{
    use Notifiable;

    public function routeNotificationForSmsApi(?Notification $notification = null): string
    {
        return $this->phone;
    }
}
```

Or provide the recipient directly in the message:

```php
return SmsApiMessage::create('Faktura została opłacona.')
    ->to('+48123123123')
    ->from('MyBrand');
```

If you use `to()`, `routeNotificationForSmsApi()` is not required for that notification.

## MMS Usage

To send an MMS, switch the message to MMS mode with `mms($subject, $smil)`:

```php
use Illuminate\Notifications\Notification;
use NotificationChannels\SmsApi\SmsApiChannel;
use NotificationChannels\SmsApi\SmsApiMessage;

class InvoiceWithAttachment extends Notification
{
    public function via(object $notifiable): array
    {
        return [SmsApiChannel::class];
    }

    public function toSmsApi(object $notifiable): SmsApiMessage
    {
        return SmsApiMessage::create()
            ->mms('Invoice 2026/04', '<smil><body><par><text src="invoice.txt"/></par></body></smil>')
            ->set('files[invoice.txt]', base64_encode('Invoice content'));
    }
}
```

You can still use `to()` to set the recipient explicitly:

```php
return SmsApiMessage::create()
    ->to('+48123123123')
    ->mms('Invoice 2026/04', '<smil><body><par><text src="invoice.txt"/></par></body></smil>')
    ->set('files[invoice.txt]', base64_encode('Invoice content'));
```

Any additional MMS-specific parameters supported by SMSAPI can be passed with `set($key, $value)`.
