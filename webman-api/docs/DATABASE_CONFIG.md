# 数据库配置优化文档

## 🎯 配置优化概述

对Webman框架的数据库配置进行了全面优化，包括字符集设置、连接池配置、安全选项等。

## 🔧 主要优化内容

### 1. 字符集配置
```php
// 完整的UTF8MB4支持
'charset' => 'utf8mb4',
'collation' => 'utf8mb4_unicode_ci',

// MySQL连接初始化命令
PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, 
                                  sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
```

### 2. 连接池配置
```php
'pool' => [
    'min_connections' => env('DB_POOL_MIN', 1),
    'max_connections' => env('DB_POOL_MAX', 10),
    'connect_timeout' => env('DB_CONNECT_TIMEOUT', 10),
    'wait_timeout' => env('DB_WAIT_TIMEOUT', 3),
    'heartbeat' => env('DB_HEARTBEAT', -1),
    'max_idle_time' => env('DB_MAX_IDLE_TIME', 60),
],
```

### 3. 安全配置
```php
'options' => [
    // 禁用持久连接（生产环境）
    PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', false),
    
    // 禁用预处理语句模拟
    PDO::ATTR_EMULATE_PREPARES => false,
    
    // 禁用多语句执行
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
    
    // 禁用本地文件导入
    PDO::MYSQL_ATTR_LOCAL_INFILE => false,
],
```

### 4. 多数据库支持
- **MySQL**: 主要数据库，支持完整配置
- **SQLite**: 开发/测试环境
- **PostgreSQL**: 可选支持

### 5. Redis配置
```php
'redis' => [
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
        'charset' => 'utf8mb4',
    ],
    'cache' => [
        'database' => env('REDIS_CACHE_DB', 1),
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'database' => env('REDIS_SESSION_DB', 2),
        'charset' => 'utf8mb4',
    ],
],
```

## 📋 环境变量配置

创建 `.env` 文件，包含以下配置：

```bash
# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=newsf1
DB_USERNAME=newsf1
DB_PASSWORD=newsf1
DB_PREFIX=
DB_STRICT_MODE=true
DB_ENGINE=InnoDB

# 数据库连接池配置
DB_POOL_MIN=1
DB_POOL_MAX=10
DB_CONNECT_TIMEOUT=10
DB_WAIT_TIMEOUT=3
DB_HEARTBEAT=-1
DB_MAX_IDLE_TIME=60

# 数据库安全配置
DB_PERSISTENT=false
DB_TIMEOUT=30
DB_SSL_VERIFY=false

# JWT配置
JWT_SECRET=your-very-long-random-secret-key-change-this-in-production
JWT_ALGORITHM=HS256
JWT_EXPIRE=86400
JWT_REFRESH_EXPIRE=604800

# Redis配置
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
```

## 🔍 字符集优化说明

### UTF8MB4支持
- **完整Unicode支持**: 支持4字节UTF-8字符（如emoji）
- **排序规则**: `utf8mb4_unicode_ci` 提供更好的排序和比较
- **兼容性**: 完全向后兼容UTF8

### 数据库初始化
```sql
-- 自动执行的初始化命令
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
```

## 🚀 性能优化

### 连接池配置
- **最小连接数**: 保持基础连接
- **最大连接数**: 限制资源使用
- **连接超时**: 防止长时间等待
- **心跳检测**: 保持连接活跃
- **空闲时间**: 自动回收空闲连接

### 查询优化
- **缓冲查询**: 提高查询性能
- **预处理语句**: 防止SQL注入
- **严格模式**: 确保数据完整性

## 🔒 安全配置

### 防注入措施
- 禁用预处理语句模拟
- 禁用多语句执行
- 禁用本地文件导入
- 启用严格模式

### 连接安全
- 关闭持久连接（生产环境）
- 设置连接超时
- SSL配置支持
- 错误处理配置

## 📊 监控配置

### 日志配置
```php
// 错误处理模式
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

// 默认获取模式
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
```

### 性能监控
- 连接池监控
- 查询性能监控
- 错误日志记录
- 超时处理

## ✅ 配置验证

### 字符集验证
```sql
-- 检查数据库字符集
SHOW VARIABLES LIKE 'character_set%';
SHOW VARIABLES LIKE 'collation%';

-- 检查表字符集
SHOW CREATE TABLE pay_admin;
```

### 连接测试
```php
// 测试数据库连接
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "数据库连接成功！字符集: " . $pdo->query("SELECT @@character_set_connection")->fetchColumn();
} catch (PDOException $e) {
    echo "连接失败: " . $e->getMessage();
}
```

## 🎯 总结

优化后的数据库配置具备：

- ✅ **完整UTF8MB4支持**: 支持所有Unicode字符
- ✅ **高性能连接池**: 优化的连接管理
- ✅ **安全防护**: 全面的安全配置
- ✅ **多环境支持**: 开发、测试、生产环境
- ✅ **监控友好**: 完整的日志和监控配置
- ✅ **易于维护**: 清晰的环境变量配置

配置已针对Webman框架进行优化，确保最佳性能和安全性。
