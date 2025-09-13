# Webman API 后端系统

基于 Webman 2.0 框架开发的企业级后端API系统，提供完整的用户管理、权限管理、操作日志等功能。

## 功能特性

### 🔐 认证系统
- JWT Token 认证
- 用户登录/登出
- 密码加密存储
- 跨域支持 (CORS)

### 👥 用户管理
- 管理员管理
- 商户/代理管理
- 用户状态管理
- 密码重置功能

### 🛡️ 权限管理 (RBAC)
- 角色管理
- 权限管理
- 角色权限关联
- 树形权限结构
- 菜单权限控制

### 📊 操作日志
- 自动记录操作日志
- 登录/登出日志
- 操作统计分析
- 日志清理功能

## 技术栈

- **框架**: Webman 2.0
- **数据库**: MySQL
- **认证**: JWT (JSON Web Token)
- **语言**: PHP 8.2+
- **架构**: 前后端分离

## 快速开始

### 环境要求
- PHP >= 8.2
- MySQL >= 5.7
- Composer

### 安装步骤

1. 克隆项目
```bash
git clone <repository-url>
cd webman-api
```

2. 安装依赖
```bash
composer install
```

3. 配置数据库
```bash
# 复制配置文件
cp config/database.php.example config/database.php

# 编辑数据库配置
vim config/database.php
```

4. 导入数据库
```sql
-- 导入数据库结构和初始数据
mysql -u username -p database_name < database.sql
```

5. 启动服务
```bash
# 开发环境
php start.php start

# 生产环境
php start.php start -d
```

### 访问地址
- API地址: `http://127.0.0.1:8787/api`
- 文档地址: `http://127.0.0.1:8787/api/docs` (如果配置了文档)

## 项目结构

```
webman-api/
├── app/                    # 应用目录
│   ├── controller/         # 控制器
│   │   ├── AuthController.php      # 认证控制器
│   │   ├── AdminController.php     # 管理员控制器
│   │   ├── MerchantController.php  # 商户控制器
│   │   ├── RoleController.php      # 角色控制器
│   │   ├── PermissionController.php # 权限控制器
│   │   └── OperationLogController.php # 操作日志控制器
│   ├── model/              # 模型
│   │   ├── User.php        # 用户模型
│   │   ├── Role.php        # 角色模型
│   │   ├── Right.php       # 权限模型
│   │   └── RoleRight.php   # 角色权限关联模型
│   ├── middleware/         # 中间件
│   │   ├── CorsMiddleware.php      # 跨域中间件
│   │   ├── JwtMiddleware.php       # JWT认证中间件
│   │   ├── OperationLogMiddleware.php # 操作日志中间件
│   │   └── PermissionMiddleware.php   # 权限验证中间件
│   └── support/            # 支持类
│       └── Database.php    # 数据库助手类
├── config/                 # 配置文件
│   ├── database.php        # 数据库配置
│   ├── route.php          # 路由配置
│   └── middleware.php     # 中间件配置
├── API.md                 # API接口文档
├── FRONTEND_GUIDE.md      # 前端开发指南
├── CHANGELOG.md           # 更新日志
└── README.md              # 项目说明
```

## API 文档

### 认证接口
- `POST /api/login` - 用户登录
- `POST /api/register` - 用户注册
- `GET /api/me` - 获取当前用户信息
- `POST /api/refresh` - 刷新Token
- `POST /api/logout` - 用户登出

### 用户管理接口
- `GET /api/admin` - 获取管理员列表
- `POST /api/admin` - 创建管理员
- `GET /api/admin/{id}` - 获取管理员详情
- `PUT /api/admin/{id}` - 更新管理员
- `DELETE /api/admin/{id}` - 删除管理员
- `GET /api/merchant` - 获取商户列表
- `POST /api/merchant` - 创建商户
- `PUT /api/merchant/{id}/reset-password` - 重置密码
- `PUT /api/merchant/{id}/toggle-status` - 切换状态

### 权限管理接口
- `GET /api/roles` - 获取角色列表
- `POST /api/roles` - 创建角色
- `GET /api/roles/{id}` - 获取角色详情
- `PUT /api/roles/{id}` - 更新角色
- `DELETE /api/roles/{id}` - 删除角色
- `GET /api/roles/{id}/rights` - 获取角色权限
- `POST /api/roles/{id}/rights` - 设置角色权限
- `GET /api/permissions` - 获取权限列表
- `GET /api/permissions/tree` - 获取权限树
- `POST /api/permissions` - 创建权限
- `PUT /api/permissions/{id}` - 更新权限
- `DELETE /api/permissions/{id}` - 删除权限

### 操作日志接口
- `GET /api/logs` - 获取操作日志
- `GET /api/logs/stats` - 获取日志统计
- `GET /api/logs/login` - 获取登录日志
- `POST /api/logs/clean` - 清理日志

详细接口文档请参考 [API.md](./API.md)

## 前端开发

前端开发指南请参考 [FRONTEND_GUIDE.md](./FRONTEND_GUIDE.md)

## 数据库设计

### 主要数据表
- `pay_admin` - 管理员表
- `pay_user` - 用户表
- `pay_role` - 角色表
- `pay_right` - 权限表
- `pay_role_right` - 角色权限关联表
- `pay_operation_log` - 操作日志表

## 开发规范

### 代码规范
- 遵循 PSR-4 自动加载规范
- 使用 PSR-12 代码风格
- 控制器方法使用 RESTful 命名
- 统一使用 JSON 响应格式

### 接口规范
- 统一响应格式：`{code, message, data, timestamp}`
- 使用 HTTP 状态码表示请求结果
- 所有接口都需要认证（除登录接口外）
- 支持分页查询

### 错误处理
- 统一错误响应格式
- 详细的错误信息记录
- 操作日志自动记录

## 部署说明

### 生产环境部署
1. 配置 Nginx 反向代理
2. 配置 SSL 证书
3. 设置进程守护
4. 配置日志轮转
5. 设置监控告警

### 性能优化
- 启用 OPcache
- 配置数据库连接池
- 使用 Redis 缓存
- 启用 Gzip 压缩

## 更新日志

详细更新日志请参考 [CHANGELOG.md](./CHANGELOG.md)

## 贡献指南

1. Fork 项目
2. 创建功能分支
3. 提交更改
4. 推送到分支
5. 创建 Pull Request

## 许可证

MIT License

## 联系方式

如有问题或建议，请联系开发团队。