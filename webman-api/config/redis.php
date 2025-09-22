<?php
/**
 * Redis配置
 */
return [
    'default' => 'cache',
    'connections' => [
        'cache' => [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'password' => getenv('REDIS_PASSWORD') ?: '',
            'database' => 0,
            'timeout' => 5,
            'retry_interval' => 100,
            'read_timeout' => 10,
            'persistent' => true,
        ],
        'session' => [
            'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
            'port' => getenv('REDIS_PORT') ?: 6379,
            'password' => getenv('REDIS_PASSWORD') ?: '',
            'database' => 1,
            'timeout' => 5,
            'retry_interval' => 100,
            'read_timeout' => 10,
            'persistent' => true,
        ],
    ],
];
