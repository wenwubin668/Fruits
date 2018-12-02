<?php

/*
 * This file is part of the overtrue/laravel-wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
    /*
     * 默认配置，将会合并到各模块中
     */
    'defaults' => [
        /*
         * 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
         */
        'response_type' => 'array',

        /*
         * 使用 Laravel 的缓存系统
         */
        'use_laravel_cache' => true,

        /*
         * 日志配置
         *
         * level: 日志级别，可选为：
         *                 debug/info/notice/warning/error/critical/alert/emergency
         * file：日志文件位置(绝对路径!!!)，要求可写权限
         */
        'log' => [
            'level' => env('WECHAT_LOG_LEVEL', 'debug'),
            'file' => env('APP_LOG_PATH') . '/wechat.' . date('Ymd') . '.log',
        ],
        'guzzle' => [
            'verify' => false,
        ],
    ],

    /*
     * 路由配置
     */
    'route' => [
    ],

    /*
     * 公众号
     */
    'official_account' => [
        //真好小店
        'zhenhao' => [
            'app_id'  => env('WECHAT_ZHENHAO_APPID'),
            'secret'  => env('WECHAT_ZHENHAO_SECRET'),
            'token'   => env('WECHAT_ZHENHAO_TOKEN'),
            'aes_key' => env('WECHAT_ZHENHAO_AES_KEY'),
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/weixin/recruit/ddwlcallback',
            ],
        ],

    ],

    /*
     * 开放平台第三方平台
     */
     'open_platform' => [
         'default' => [
             'app_id'  => env('WECHAT_OPEN_PLATFORM_APPID', ''),
             'secret'  => env('WECHAT_OPEN_PLATFORM_SECRET', ''),
             'token'   => env('WECHAT_OPEN_PLATFORM_TOKEN', ''),
             'aes_key' => env('WECHAT_OPEN_PLATFORM_AES_KEY', ''),
         ],
         //线上一点一滴大号配置
         'ydyd_online' => [
             'app_id'  => 'wx87cf036d5eaf1535',
             'secret'  => '7010be59102365f2fad11736f2323f7b',
             'token'   => '185e37a2938ca2d1cc93dcc4d0e31c5c',
             'aes_key' => 'd9172a8a31043d1d60c8af719acaaa89DdWeilyi666',
         ],
     ],
    /*
     * 微信支付
     */
     'payment' => [
         'default' => [
             'app_id'   => env('WECHAT_PAYMENT_APPID'),
             'mch_id'   => env('WECHAT_PAYMENT_MCH_ID'),
             'key'      => env('WECHAT_PAYMENT_KEY'),
             'cert_path'=> env('WECHAT_PAYMENT_CERT_PATH'),
             'key_path' => env('WECHAT_PAYMENT_KEY_PATH'),
             'notify_url'=> 'http://example.com/payments/wechat-notify',// 默认支付结果通知地址
         ],
     ],
    //微信素材下载地址
    'file_base_url' => 'https://api.weixin.qq.com',
    //临时图片目录
    'temp_pic_url' => '/data/service_data/php/uploadtmp/',    //storage_path('framework/cache/data')
    //素材公众号APPID
    'source_appid' => env('WECHAT_OFFICIAL_APPID_TEST','wx6effee34c1c3dbed'),
];
