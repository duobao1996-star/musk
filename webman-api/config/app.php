<?php
/**
 * 应用配置
 */
return [
    'debug' => getenv('APP_DEBUG') ?: false,
    'env' => getenv('APP_ENV') ?: 'production',
    'timezone' => 'Asia/Shanghai',
    
    // 安全配置
    'security' => [
        'cors' => [
            'allowed_origins' => explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173,http://127.0.0.1:5173,http://localhost:3000,http://127.0.0.1:3000'),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Request-ID'],
            'max_age' => 86400,
            'credentials' => false,
        ],
        'csrf' => [
            'enabled' => getenv('CSRF_ENABLED') ?: false,
            'token_name' => '_token',
            'expire_time' => 3600,
        ],
        'xss' => [
            'enabled' => true,
            'auto_escape' => true,
        ],
        'sql_injection' => [
            'enabled' => true,
            'block_queries' => true,
        ],
    ],
    
    // 频率限制配置
    'rate_limit' => [
        'enabled' => getenv('RATE_LIMIT_ENABLED') ?: true,
        'max_requests' => getenv('RATE_LIMIT_MAX_REQUESTS') ?: 100,
        'window' => getenv('RATE_LIMIT_WINDOW') ?: 60, // 秒
        'skip_successful_requests' => true,
        'skip_failed_requests' => false,
    ],
    
    // 日志配置
    'log' => [
        'level' => getenv('LOG_LEVEL') ?: 'info',
        'file' => __DIR__ . '/../runtime/logs/app.log',
        'max_files' => 30,
        'max_file_size' => 10485760, // 10MB
        'date_format' => 'Y-m-d H:i:s',
        'include_stack_trace' => true,
    ],
    
    // 缓存配置
    'cache' => [
        'driver' => getenv('CACHE_DRIVER') ?: 'file',
        'prefix' => getenv('CACHE_PREFIX') ?: 'musk_admin',
        'ttl' => getenv('CACHE_TTL') ?: 3600,
    ],
    
    // 会话配置
    'session' => [
        'driver' => getenv('SESSION_DRIVER') ?: 'file',
        'lifetime' => getenv('SESSION_LIFETIME') ?: 120, // 分钟
        'encrypt' => getenv('SESSION_ENCRYPT') ?: false,
        'path' => '/',
        'domain' => null,
        'secure' => getenv('SESSION_SECURE') ?: false,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    // 文件上传配置
    'upload' => [
        'max_size' => getenv('UPLOAD_MAX_SIZE') ?: 2048, // KB
        'allowed_types' => explode(',', getenv('UPLOAD_ALLOWED_TYPES') ?: 'jpg,jpeg,png,gif,pdf,doc,docx'),
        'path' => getenv('UPLOAD_PATH') ?: 'storage/uploads',
        'create_thumbnails' => true,
    ],
    
    // API配置
    'api' => [
        'version' => 'v1',
        'prefix' => 'api',
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // 毫秒
    ],
];