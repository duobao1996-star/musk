<?php
/**
 * JWT配置
 */
return [
    'secret' => getenv('JWT_SECRET') ?: 'your-very-long-random-secret-key-change-this-in-production',
    'algorithm' => 'HS256',
    'expire' => 24 * 60 * 60, // 24小时
    'refresh_expire' => 7 * 24 * 60 * 60, // 7天
];
