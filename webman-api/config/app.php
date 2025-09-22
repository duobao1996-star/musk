<?php
/**
 * 应用配置
 */
return [
    'debug' => getenv('APP_DEBUG') ?: false,
    'env' => getenv('APP_ENV') ?: 'production',
    
    // 安全配置
    'security' => [
        'cors' => [
            'allowed_origins' => explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:3000,http://127.0.0.1:3000'),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'max_age' => 86400,
        ],
    ],
    
    // 频率限制配置
    'rate_limit' => [
        'max_requests' => getenv('RATE_LIMIT_MAX_REQUESTS') ?: 100,
        'window' => getenv('RATE_LIMIT_WINDOW') ?: 60, // 秒
    ],
    
    // 日志配置
    'log' => [
        'level' => getenv('LOG_LEVEL') ?: 'info',
        'file' => __DIR__ . '/../runtime/logs/app.log',
        'max_files' => 30,
    ],
];