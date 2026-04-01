<?php

return [
    'base_url' => env('SMSAPI_BASE_URL', 'https://api.smsapi.example'),
    'token' => env('SMSAPI_TOKEN'),
    'from' => env('SMSAPI_FROM'),
    'timeout' => (int) env('SMSAPI_TIMEOUT', 10),
];
