<?php

use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'errorlog'],
        ],
        'single' => [
            'driver'		 => 'single',
            'path'			 => env('APP_LOG_PATH', '/data/service_logs/php/laravel') . '/wechatapi.log',
            'level'			 => 'debug',
            'formatter'		 => 'Monolog\Formatter\LineFormatter',
            'formatter_with' => ['format' => "[%datetime%] [%log_id%] [%use_time%] %level_token% %short_message%\n"],
        ],
        'errorlog' => [
            'driver'		 => 'single',
            'path'           => env('APP_LOG_PATH', '/data/service_logs/php/laravel') . '/wechatapi-error.log',
            'level'			 => 'error',
            'formatter'		 => 'App\Log\LineFormatter',
            'formatter_with' => ['format' => "%datetime% %log_id% %level_token% %message%\n"],
        ],
    ],

];
