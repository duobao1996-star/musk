<?php
/**
 * ThinkORM数据库配置
 * Webman框架实际使用的数据库配置
 */

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type' => 'mysql',
            // 服务器地址
            'hostname' => getenv('DB_HOST') ?: '127.0.0.1',
            // 数据库名
            'database' => getenv('DB_DATABASE') ?: 'newsf1',
            // 数据库用户名
            'username' => getenv('DB_USERNAME') ?: 'newsf1',
            // 数据库密码
            'password' => getenv('DB_PASSWORD') ?: 'newsf1',
            // 数据库连接端口
            'hostport' => getenv('DB_PORT') ?: '3306',
            
            // 完整的字符集配置 - UTF8MB4支持
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            
            // 数据库表前缀
            'prefix' => getenv('DB_PREFIX') ?: '',
            
            // 断线重连
            'break_reconnect' => true,
            
            // 严格模式
            'strict' => getenv('DB_STRICT_MODE') ?: true,
            
            // 数据库连接参数 - 完整的PDO配置
            'params' => [
                // 错误处理模式
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                // 默认获取模式
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // 连接超时
                \PDO::ATTR_TIMEOUT => getenv('DB_TIMEOUT') ?: 30,
                // 持久连接（生产环境建议关闭）
                \PDO::ATTR_PERSISTENT => getenv('DB_PERSISTENT') ?: false,
                
                // MySQL特定配置
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, 
                                                    sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
                // 使用缓冲查询
                \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                // 禁用预处理语句模拟
                \PDO::ATTR_EMULATE_PREPARES => false,
                // 禁用多语句执行（安全考虑）
                \PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
                // SSL配置
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => getenv('DB_SSL_VERIFY') ?: false,
                // 禁用本地文件导入
                \PDO::MYSQL_ATTR_LOCAL_INFILE => false,
            ],
            
            // 连接池配置 - Webman优化版本
            'pool' => [
                'max_connections' => getenv('DB_POOL_MAX') ?: 10, // 最大连接数
                'min_connections' => getenv('DB_POOL_MIN') ?: 1, // 最小连接数
                'wait_timeout' => getenv('DB_WAIT_TIMEOUT') ?: 3, // 从连接池获取连接等待超时时间
                'idle_timeout' => getenv('DB_IDLE_TIMEOUT') ?: 60, // 连接最大空闲时间，超过该时间会被回收
                'heartbeat_interval' => getenv('DB_HEARTBEAT_INTERVAL') ?: 50, // 心跳检测间隔，需要小于60秒
                'connect_timeout' => getenv('DB_CONNECT_TIMEOUT') ?: 10, // 连接超时时间
            ],
            
            // 查询日志（开发环境）
            'debug' => getenv('APP_DEBUG') ?: false,
            
            // 自动时间戳
            'auto_timestamp' => false,
            
            // 字段缓存
            'fields_cache' => getenv('DB_FIELDS_CACHE') ?: false,
        ],
        
        // SQLite配置（开发/测试环境）
        'sqlite' => [
            'type' => 'sqlite',
            'database' => getenv('DB_DATABASE') ?: __DIR__ . '/../database/database.sqlite',
            'prefix' => getenv('DB_PREFIX') ?: '',
            'charset' => 'utf8mb4',
            'break_reconnect' => false,
            'pool' => [
                'max_connections' => 5,
                'min_connections' => 1,
                'wait_timeout' => 3,
                'idle_timeout' => 60,
            ],
        ],
    ],
    
    // 自定义分页类
    'paginator' => '',
    
    // 查询构建器配置
    'query' => [
        // 是否自动转换字段名
        'auto_convert_field' => true,
        // 是否自动转换表名
        'auto_convert_table' => true,
    ],
];
