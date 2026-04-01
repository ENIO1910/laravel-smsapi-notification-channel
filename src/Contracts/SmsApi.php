<?php

declare(strict_types=1);

namespace NotificationChannels\SmsApi\Contracts;

use NotificationChannels\SmsApi\Dto\SmsApiResponse;
use NotificationChannels\SmsApi\SmsApiMessage;

interface SmsApi
{
    public function send(SmsApiMessage $message): SmsApiResponse;
}
