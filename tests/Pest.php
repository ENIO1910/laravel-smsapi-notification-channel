<?php

declare(strict_types=1);

use Illuminate\Container\Container;

if (! function_exists('config')) {
    function config(?string $key = null, mixed $default = null): mixed
    {
        $config = Container::getInstance()->make('config');

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
    }
}
