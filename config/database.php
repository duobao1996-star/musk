<?php
/**
 * 数据库配置
 */

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: 3306,
            'database' => getenv('DB_DATABASE') ?: 'newsf1',
            'username' => getenv('DB_USERNAME') ?: 'newsf1',
            'password' => getenv('DB_PASSWORD') ?: 'newsf1',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => 30,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../database/database.sqlite',
            'prefix' => '',
        ],
    ],
];
