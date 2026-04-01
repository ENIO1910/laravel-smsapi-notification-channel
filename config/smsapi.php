<?php

declare(strict_types=1);

return [
    'service' => env('SMSAPI_SERVICE', 'pl'),
    'uri' => env('SMSAPI_URI'),
    'token' => env('SMSAPI_TOKEN'),
    'from' => env('SMSAPI_FROM'),
    'timeout' => (int) env('SMSAPI_TIMEOUT', 10),
];
