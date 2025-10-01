# Musk管理系统 API

基于 Webman + ThinkORM 构建的权限管理系统后端API。

## 功能特性

- 🔐 JWT认证授权
- 👥 基于角色的权限控制(RBAC)
- 📊 操作日志记录
- 📈 性能监控
- 🗑️ 软删除支持
- 🔄 健康检查

## 技术栈

- **框架**: Webman
- **ORM**: ThinkORM
- **认证**: JWT
- **数据库**: MySQL
- **PHP版本**: 8.0+

## 快速开始

### 1. 环境要求

- PHP 8.0+
- MySQL 5.7+
- Composer

### 2. 安装依赖

```bash
composer install
```

### 3. 配置数据库

复制环境配置文件：
```bash
cp env.example .env
```

编辑 `.env` 文件，配置数据库连接信息：
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=newsf1
DB_USERNAME=newsf1
DB_PASSWORD=newsf1
```

### 4. 初始化数据库

执行数据库初始化脚本：
```bash
mysql -h 127.0.0.1 -u newsf1 -pnewsf1 --default-character-set=utf8mb4 newsf1 < database/rebuild_database.sql
```

### 5. 启动服务

```bash
php start.php start
```

服务将在 `http://localhost:8787` 启动。

### 6. 一键冒烟测试

确保服务已启动后，执行：

```bash
bash scripts/smoke.sh
```

可选环境变量：

```bash
BASE_URL=http://127.0.0.1:8787 USERNAME=admin PASSWORD=Admin@12345 bash scripts/smoke.sh
```

## 默认账户

| 用户名 | 密码 | 角色 | 权限 |
|--------|------|------|------|
| admin | Admin@12345 | Super Admin | 所有权限 |
| manager | Admin@12345 | Admin | 大部分管理权限 |
| operator | Admin@12345 | Operator | 基础操作权限 |
| viewer | Admin@12345 | Viewer | 只读权限 |

## API文档

### 认证接口

- `POST /api/login` - 用户登录
- `POST /api/logout` - 用户登出
- `POST /api/refresh-token` - 刷新令牌
- `GET /api/me` - 获取个人信息

### 权限管理

- `GET /api/permissions` - 获取权限列表
- `POST /api/permissions` - 创建权限
- `PUT /api/permissions/{id}` - 更新权限
- `DELETE /api/permissions/{id}` - 删除权限
- `GET /api/permissions/tree` - 获取权限树
- `GET /api/permissions/menu` - 获取菜单权限

### 角色管理

- `GET /api/roles` - 获取角色列表
- `POST /api/roles` - 创建角色
- `PUT /api/roles/{id}` - 更新角色
- `DELETE /api/roles/{id}` - 删除角色
- `GET /api/roles/{id}/rights` - 获取角色权限
- `POST /api/roles/{id}/rights` - 设置角色权限
- `GET /api/roles/all-rights-tree` - 获取全部权限树

### 操作日志

- `GET /api/operation-logs` - 获取操作日志
- `GET /api/operation-logs/stats` - 获取操作统计
- `GET /api/operation-logs/login` - 获取登录日志
- `POST /api/operation-logs/clean` - 清理旧日志

### 软删除日志

- `GET /api/soft-delete/logs` - 获取已删除日志
- `POST /api/soft-delete/logs/restore` - 恢复日志
- `DELETE /api/soft-delete/logs/force` - 彻底删除日志
- `POST /api/soft-delete/cleanup` - 回收站清理

### 性能监控

- `GET /api/performance/stats` - 性能统计
- `GET /api/performance/slow-queries` - 慢查询列表

### 统一响应规范

成功：

```json
{
  "code": 200,
  "message": "ok",
  "data": {},
  "timestamp": 1700000000
}
```

分页：

```json
{
  "code": 200,
  "message": "获取成功",
  "data": [ ... ],
  "pagination": { "total": 100, "page": 1, "limit": 10, "pages": 10 },
  "timestamp": 1700000000
}
```

错误：

```json
{ "code": 401, "message": "未登录或已过期" }
```

### 系统检查

- `GET /api/health` - 健康检查
- `GET /api/ready` - 就绪检查

## 权限系统

系统采用基于角色的权限控制(RBAC)模型：

### 角色类型

1. **Super Admin** - 超级管理员
   - 拥有所有权限
   - 可以管理其他管理员

2. **Admin** - 普通管理员
   - 拥有大部分管理权限
   - 不能执行敏感操作（如清理日志）

3. **Operator** - 操作员
   - 拥有基础操作权限
   - 主要是查看和基础操作

4. **Viewer** - 只读用户
   - 只能查看数据
   - 不能执行修改操作

### 权限类型

- **菜单权限** (menu=1) - 显示在左侧菜单中
- **接口权限** (menu=0) - 具体的API操作权限

## 开发说明

### 项目结构

```
├── app/                    # 应用代码
│   ├── controller/         # 控制器
│   ├── middleware/         # 中间件
│   ├── model/             # 模型
│   └── support/           # 支持类
├── config/                # 配置文件
├── database/              # 数据库相关
├── public/                # 公共文件
├── scripts/               # 脚本文件
└── runtime/               # 运行时文件
```

### 中间件

- `JwtMiddleware` - JWT认证
- `PermissionMiddleware` - 权限检查
- `OperationLogMiddleware` - 操作日志
- `PerformanceMiddleware` - 性能监控
- `CorsMiddleware` - 跨域处理
- `RateLimitMiddleware` - 频率限制

### 数据库

主要数据表：
- `pay_admin` - 管理员表
- `pay_role` - 角色表
- `pay_right` - 权限表
- `pay_role_right` - 角色权限关联表
- `pay_operation_log` - 操作日志表
- `pay_performance_metrics` - 性能监控表
- `pay_system_config` - 系统配置表

## 部署

### 生产环境

1. 配置环境变量
2. 设置数据库连接
3. 执行数据库初始化
4. 启动服务：
   ```bash
   php start.php start -d
   ```

### Docker部署

```dockerfile
FROM php:8.0-cli
# 安装扩展和依赖
# 复制代码
# 启动服务
```

## 许可证

MIT License

## 更新日志

### v2.0.0
- 重构权限系统
- 优化数据库结构
- 完善API接口
- 增加性能监控
- 支持软删除