<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HTTPS Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls how the application handles HTTPS redirects
    | and protocol detection in different environments.
    |
    */

    // 是否强制HTTPS（默认禁用，因为使用Cloudflare隧道）
    'force' => $_ENV['FORCE_HTTPS'] ?? false,

    // 需要强制HTTPS的主机列表
    'hosts' => [
        // 可以在这里添加特定的域名
        // 'example.com',
        // 'www.example.com',
    ],

    // 是否检测Cloudflare代理（禁用，因为不需要HTTPS重定向）
    'detect_cloudflare' => $_ENV['DETECT_CLOUDFLARE'] ?? false,

    // Cloudflare相关的HTTP头
    'cloudflare_headers' => [
        'HTTP_CF_VISITOR',
        'HTTP_CF_RAY',
        'HTTP_CF_CONNECTING_IP',
    ],

    // 本地开发环境的主机模式
    'local_hosts' => [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
    ],

    // 是否在API请求中也强制HTTPS（禁用，因为使用Cloudflare隧道）
    'force_on_api' => $_ENV['FORCE_HTTPS_ON_API'] ?? false,

    // HTTPS重定向状态码
    'redirect_status' => 301,
];