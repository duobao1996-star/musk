# 🚀 项目部署和使用指南

## 📋 项目概述

这是一个基于 **Webman + Vue3 + Element Plus** 的现代化管理系统，具备完整的 RBAC 权限控制、系统监控、操作日志等功能。

## 🏗️ 技术架构

### 后端
- **框架**: Webman (PHP 8.2+)
- **数据库**: MySQL 8.0+
- **ORM**: ThinkORM
- **认证**: JWT (HS256)
- **密码加密**: Argon2ID

### 前端
- **框架**: Vue 3 + TypeScript
- **UI库**: Element Plus
- **状态管理**: Pinia
- **路由**: Vue Router
- **HTTP客户端**: Axios

## 🚀 快速开始

### 1. 环境要求

#### 后端环境
- PHP 8.2+
- MySQL 8.0+
- Composer
- Webman

#### 前端环境
- Node.js 16+
- NPM 或 Yarn

### 2. 数据库配置

#### 数据库连接信息
```php
// config/think-orm.php
'connections' => [
    'mysql' => [
        'hostname' => '127.0.0.1',
        'database' => 'newsf1',
        'username' => 'newsf1',
        'password' => 'newsf1',
        'hostport' => '3306',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ]
]
```

#### 执行数据库迁移
```bash
# 进入后端目录
cd webman-api

# 执行数据库迁移
mysql -u newsf1 -pnewsf1 newsf1 < database/migrations/basic_migration.sql
```

### 3. 后端启动

```bash
# 进入后端目录
cd webman-api

# 安装依赖
composer install

# 启动服务
php start.php start

# 后台运行
php start.php start -d

# 停止服务
php start.php stop

# 重启服务
php start.php restart
```

### 4. 前端启动

```bash
# 进入前端目录
cd musk-admin

# 安装依赖
npm install

# 启动开发服务器
npm run dev

# 构建生产版本
npm run build
```

## 🔐 默认账号

### 管理员账号
- **用户名**: admin
- **密码**: password
- **角色**: 超级管理员
- **权限**: 所有权限

### 其他测试账号
- **manager**: password (普通管理员)
- **operator**: password (操作员)
- **viewer**: password (只读用户)

## 📊 功能模块

### 1. 仪表盘
- ✅ 系统统计信息
- ✅ 快速操作入口
- ✅ 系统状态监控
- ✅ 数据概览

### 2. 系统管理
- ✅ 系统信息查看
- ✅ 系统配置管理
- ✅ 系统状态监控
- ✅ 缓存管理

### 3. 权限管理
- ✅ 角色管理 (CRUD)
- ✅ 权限管理 (CRUD)
- ✅ 权限分配
- ✅ 权限树展示

### 4. 管理员管理
- ✅ 管理员账号 (CRUD)
- ✅ 密码重置
- ✅ 状态管理
- ✅ 角色分配

### 5. 操作日志
- ✅ 日志查看
- ✅ 日志搜索
- ✅ 日志统计
- ✅ 日志清理

### 6. 性能监控
- ✅ 系统性能
- ✅ 数据库状态
- ✅ 慢查询分析
- ✅ 性能趋势

## 🔧 配置说明

### 1. 数据库配置

```php
// config/think-orm.php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'type' => 'mysql',
            'hostname' => getenv('DB_HOST') ?: '127.0.0.1',
            'database' => getenv('DB_DATABASE') ?: 'newsf1',
            'username' => getenv('DB_USERNAME') ?: 'newsf1',
            'password' => getenv('DB_PASSWORD') ?: 'newsf1',
            'hostport' => getenv('DB_PORT') ?: '3306',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            // ... 其他配置
        ]
    ]
];
```

### 2. JWT配置

```php
// config/jwt.php
return [
    'secret' => 'your-secret-key',
    'expire' => 86400, // 24小时
    'algorithm' => 'HS256',
];
```

### 3. 前端配置

```typescript
// src/utils/request.ts
const request = axios.create({
  baseURL: '/api',
  timeout: 30000,
  // ... 其他配置
});
```

## 🔒 权限系统

### 1. 角色定义

#### 超级管理员 (Super Admin)
- 拥有所有权限
- 可以管理所有用户和角色
- 可以访问所有功能模块

#### 普通管理员 (Admin)
- 拥有管理权限
- 可以管理用户和角色
- 可以查看系统信息

#### 操作员 (Operator)
- 拥有操作权限
- 可以查看日志和监控
- 可以进行日常操作

#### 只读用户 (Viewer)
- 仅有查看权限
- 可以查看仪表盘和日志
- 不能进行任何修改操作

### 2. 权限控制

#### 前端权限控制
- 路由权限验证
- 菜单动态生成
- 按钮权限控制

#### 后端权限控制
- API权限验证
- 中间件权限检查
- 数据库权限控制

## 📱 访问地址

### 开发环境
- **前端**: http://localhost:5173
- **后端**: http://localhost:8787
- **API文档**: http://localhost:8787/api-docs

### 生产环境
- **前端**: http://your-domain.com
- **后端**: http://your-domain.com:8787
- **API文档**: http://your-domain.com:8787/api-docs

## 🐛 常见问题

### 1. 数据库连接失败
```bash
# 检查数据库服务是否启动
sudo systemctl status mysql

# 检查数据库配置
mysql -u newsf1 -pnewsf1 -h 127.0.0.1 -P 3306
```

### 2. 后端服务启动失败
```bash
# 检查PHP版本
php -v

# 检查端口是否被占用
netstat -tlnp | grep 8787

# 查看错误日志
tail -f runtime/logs/webman.log
```

### 3. 前端构建失败
```bash
# 清除缓存
npm cache clean --force

# 删除node_modules重新安装
rm -rf node_modules
npm install
```

### 4. 权限验证失败
- 检查JWT配置是否正确
- 检查数据库权限数据是否完整
- 检查中间件配置是否正确

## 🔧 开发指南

### 1. 添加新功能模块

#### 后端
1. 创建控制器 `app/controller/YourController.php`
2. 创建模型 `app/model/YourModel.php`
3. 添加路由 `config/route.php`
4. 创建数据库迁移

#### 前端
1. 创建页面组件 `src/views/your/YourView.vue`
2. 创建API接口 `src/api/your.ts`
3. 添加路由 `src/router/index.ts`
4. 更新菜单权限

### 2. 添加新权限

1. 在数据库中添加权限记录
2. 更新权限树结构
3. 分配权限给相应角色
4. 更新前端菜单配置

### 3. 自定义主题

```scss
// src/styles/variables.scss
:root {
  --el-color-primary: #409eff;
  --el-color-success: #67c23a;
  --el-color-warning: #e6a23c;
  --el-color-danger: #f56c6c;
}
```

## 📈 性能优化

### 1. 数据库优化
- 添加适当的索引
- 优化查询语句
- 使用连接池
- 定期清理日志

### 2. 前端优化
- 使用懒加载
- 代码分割
- 图片优化
- 缓存策略

### 3. 后端优化
- 启用OPcache
- 使用Redis缓存
- 优化数据库查询
- 启用压缩

## 🔐 安全建议

### 1. 生产环境
- 修改默认密码
- 使用HTTPS
- 配置防火墙
- 定期更新依赖

### 2. 数据库安全
- 使用强密码
- 限制数据库访问
- 定期备份
- 监控异常访问

### 3. 代码安全
- 输入验证
- SQL注入防护
- XSS防护
- CSRF保护

## 📞 技术支持

### 1. 文档
- 项目文档: `/COMPLETE_PROJECT_SUMMARY.md`
- 数据库设计: `/webman-api/database/COMPLETE_DATABASE_DESIGN.md`
- API文档: `http://localhost:8787/api-docs`

### 2. 日志
- 后端日志: `webman-api/runtime/logs/`
- 前端日志: 浏览器控制台
- 数据库日志: MySQL错误日志

### 3. 调试
- 开启调试模式: `config/app.php` 设置 `debug => true`
- 查看详细错误信息
- 使用浏览器开发者工具

## 🎉 项目特色

- ✅ **现代化UI设计** - 简洁美观的界面
- ✅ **完整的RBAC权限系统** - 灵活的权限控制
- ✅ **实时系统监控** - 全面的性能监控
- ✅ **详细的操作日志** - 完整的审计追踪
- ✅ **响应式设计** - 支持各种设备
- ✅ **高性能架构** - 优化的前后端架构
- ✅ **易于扩展** - 模块化设计
- ✅ **安全可靠** - 多层安全防护

## 📝 更新日志

### v1.0.0 (2024-01-XX)
- ✅ 初始版本发布
- ✅ 完整的RBAC权限系统
- ✅ 系统监控功能
- ✅ 操作日志功能
- ✅ 管理员管理功能
- ✅ 现代化UI设计

---

**项目已准备就绪，可以开始使用！** 🚀
