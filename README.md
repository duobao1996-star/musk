# 🚀 Webman API 2.0

> 基于 Webman 2.0 框架开发的企业级后端API系统，支持前后端分离架构

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Webman Version](https://img.shields.io/badge/Webman-2.0-green.svg)](https://www.workerman.net/doc/webman/)
[![MySQL Version](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

## ✨ 功能特性

- 🔐 **JWT认证系统** - 安全的用户认证和授权
- 👥 **RBAC权限管理** - 基于角色的访问控制
- 👤 **用户管理模块** - 管理员、商户、代理管理
- 📊 **操作日志系统** - 完整的操作审计记录
- ⚡ **性能监控** - API性能指标实时监控
- 🗑️ **软删除机制** - 数据安全删除和恢复
- 📚 **美观的API文档** - 可视化交互式文档
- 🚀 **Redis缓存支持** - 高性能缓存机制
- 🏊 **数据库连接池** - 高效的数据库连接管理
- 🛡️ **安全防护** - CSRF保护、XSS防护、限流

## 🛠️ 技术栈

- **后端框架**: Webman 2.0
- **PHP版本**: 8.2+
- **数据库**: MySQL 8.0+
- **缓存**: Redis 6.0+
- **认证**: JWT (JSON Web Token)
- **架构**: RESTful API + 前后端分离

## 🚀 快速开始

### 环境要求

- PHP >= 8.2
- MySQL >= 8.0
- Redis >= 6.0 (可选，支持文件缓存降级)
- Composer

### 安装部署

```bash
# 1. 克隆项目
git clone https://github.com/duobao1996-star/musk.git
cd musk

# 2. 安装依赖
composer install

# 3. 配置环境变量
cp env.example .env
# 编辑 .env 文件配置数据库和Redis

# 4. 启动服务
php start.php start
```

### 服务地址

- **开发环境**: http://127.0.0.1:8787
- **API文档**: http://127.0.0.1:8787/api-docs
- **API基础路径**: http://127.0.0.1:8787/api

## 📚 API文档

项目提供了美观的可视化API文档，包含：

- 📖 完整的接口说明
- 🔧 交互式API测试
- 📋 参数详细说明
- 💡 使用示例

访问地址: http://127.0.0.1:8787/api-docs

## 🗂️ 项目结构

```
webman-api/
├── app/                    # 应用目录
│   ├── controller/         # 控制器
│   ├── model/             # 数据模型
│   ├── middleware/        # 中间件
│   ├── support/           # 支持类
│   └── view/              # 视图模板
├── config/                # 配置文件
├── database/              # 数据库相关
├── public/                # 公共资源
├── runtime/               # 运行时文件
├── support/               # 框架支持
├── API.md                 # API文档
└── README.md              # 项目说明
```

## 🔧 配置说明

### 数据库配置

```php
// config/database.php
'connections' => [
    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'newsf1',
        'username' => 'newsf1',
        'password' => 'newsf1',
        'charset' => 'utf8mb4',
    ],
],
```

### Redis配置

```php
// config/redis.php
'connections' => [
    'cache' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => 0,
    ],
],
```

## 🔐 认证说明

### JWT认证

所有需要认证的接口都需要在请求头中携带JWT令牌：

```http
Authorization: Bearer <your_jwt_token>
```

### 获取令牌

```bash
curl -X POST http://127.0.0.1:8787/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'
```

## 📊 主要功能模块

### 1. 用户认证
- 用户登录/登出
- JWT令牌管理
- 令牌刷新机制

### 2. 用户管理
- 管理员管理
- 商户管理
- 代理管理
- 密码重置

### 3. 权限管理
- 角色管理
- 权限管理
- RBAC权限控制

### 4. 操作日志
- 登录日志
- 操作记录
- 日志统计
- 日志清理

### 5. 性能监控
- API性能统计
- 慢查询监控
- 内存使用监控

### 6. 软删除管理
- 数据软删除
- 数据恢复
- 批量操作
- 数据清理

## 🚀 部署指南

### 生产环境部署

```bash
# 1. 设置环境变量
export APP_ENV=production

# 2. 启动服务（守护进程模式）
php start.php start -d

# 3. 停止服务
php start.php stop

# 4. 重启服务
php start.php restart

# 5. 查看状态
php start.php status
```

### Docker部署（可选）

```dockerfile
FROM php:8.2-cli
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql
WORKDIR /app
COPY . .
RUN composer install --no-dev --optimize-autoloader
EXPOSE 8787
CMD ["php", "start.php", "start"]
```

## 🤝 贡献指南

1. Fork 本仓库
2. 创建特性分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 开启 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情。

## 📞 技术支持

- **项目仓库**: https://github.com/duobao1996-star/musk
- **问题反馈**: [Issues](https://github.com/duobao1996-star/musk/issues)
- **技术文档**: [API.md](API.md)

## 🙏 致谢

感谢以下开源项目：

- [Webman](https://www.workerman.net/doc/webman/) - 高性能PHP框架
- [Workerman](https://www.workerman.net/) - PHP异步网络框架
- [Firebase JWT](https://github.com/firebase/php-jwt) - JWT处理库

---

⭐ 如果这个项目对您有帮助，请给它一个星标！