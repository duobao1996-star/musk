# Webman API 2.0 接口文档

## 📋 目录

- [项目概述](#项目概述)
- [快速开始](#快速开始)
- [认证授权](#认证授权)
- [通用规范](#通用规范)
- [错误处理](#错误处理)
- [接口列表](#接口列表)
  - [认证接口](#认证接口)
  - [用户管理](#用户管理)
  - [角色管理](#角色管理)
  - [权限管理](#权限管理)
  - [操作日志](#操作日志)
  - [性能监控](#性能监控)
  - [软删除管理](#软删除管理)

---

## 📖 项目概述

### 基本信息

- **项目名称**: Webman API 2.0 后端系统
- **版本**: 2.0.0
- **技术栈**: PHP 8.2 + Webman 2.0 + MySQL 8.0 + Redis
- **架构模式**: 前后端分离 + RESTful API
- **认证方式**: JWT (JSON Web Token)

### 核心功能

- ✅ **用户认证**: JWT令牌认证、登录登出
- ✅ **用户管理**: 管理员、商户、代理管理
- ✅ **权限控制**: RBAC角色权限系统
- ✅ **操作审计**: 完整的操作日志记录
- ✅ **性能监控**: API性能指标监控
- ✅ **软删除**: 数据安全删除机制

---

## 🚀 快速开始

### 环境要求

- PHP >= 8.2
- MySQL >= 8.0
- Redis >= 6.0
- Composer

### 安装部署

```bash
# 1. 安装依赖
composer install

# 2. 配置环境变量
cp .env.example .env
# 编辑 .env 文件配置数据库和Redis

# 3. 启动服务
php start.php start
```

### 服务地址

- **开发环境**: `http://127.0.0.1:8787`
- **API基础路径**: `http://127.0.0.1:8787/api`

---

## 🔐 认证授权

### JWT认证

所有需要认证的接口都需要在请求头中携带JWT令牌：

```http
Authorization: Bearer <your_jwt_token>
```

### 获取令牌

通过登录接口获取JWT令牌：

```http
POST /api/login
Content-Type: application/json

{
    "username": "admin",
    "password": "password123"
}
```

### 令牌刷新

令牌即将过期时可以通过刷新接口获取新令牌：

```http
POST /api/refresh-token
Authorization: Bearer <current_token>
```

---

## 📝 通用规范

### 请求格式

#### 请求头

```http
Content-Type: application/json
Authorization: Bearer <token>  # 需要认证的接口
X-CSRF-Token: <csrf_token>    # POST/PUT/DELETE请求
```

#### 请求参数

- **GET请求**: 参数通过URL查询字符串传递
- **POST/PUT请求**: 参数通过JSON格式传递
- **分页参数**: `page` (页码，默认1), `limit` (每页数量，默认15)

### 响应格式

#### 成功响应

```json
{
    "code": 200,
    "message": "操作成功",
    "data": {
        // 响应数据
    },
    "timestamp": 1640995200
}
```

#### 分页响应

```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        // 数据列表
    ],
    "pagination": {
        "total": 100,
        "page": 1,
        "limit": 15,
        "pages": 7
    },
    "timestamp": 1640995200
}
```

### HTTP状态码

| 状态码 | 说明 | 使用场景 |
|--------|------|----------|
| 200 | 成功 | 请求成功处理 |
| 201 | 创建成功 | 资源创建成功 |
| 400 | 请求错误 | 参数验证失败 |
| 401 | 未认证 | 令牌无效或过期 |
| 403 | 权限不足 | 没有访问权限 |
| 404 | 资源不存在 | 请求的资源不存在 |
| 422 | 参数错误 | 业务逻辑验证失败 |
| 500 | 服务器错误 | 内部服务器错误 |

---

## ⚠️ 错误处理

### 错误响应格式

```json
{
    "code": 400,
    "message": "参数验证失败",
    "data": {
        "errors": {
            "username": ["用户名不能为空"],
            "email": ["邮箱格式不正确"]
        }
    },
    "timestamp": 1640995200
}
```

### 常见错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| 400 | 请求参数错误 | 检查请求参数格式和内容 |
| 401 | 认证失败 | 检查JWT令牌是否有效 |
| 403 | 权限不足 | 确认用户是否有相应权限 |
| 404 | 资源不存在 | 检查请求的URL和资源ID |
| 422 | 业务逻辑错误 | 检查业务规则和数据状态 |
| 429 | 请求频率限制 | 降低请求频率 |
| 500 | 服务器内部错误 | 联系技术支持 |

---

## 🔗 接口列表

## 🔑 认证接口

### 用户登录

**接口地址**: `POST /api/login`

**接口描述**: 用户登录获取JWT令牌

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 是 | 用户名 |
| password | string | 是 | 密码 |

**请求示例**:
```json
{
    "username": "admin",
    "password": "password123"
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "登录成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 86400,
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@example.com",
            "role_id": 1,
            "user_type": "admin"
        }
    },
    "timestamp": 1640995200
}
```

### 用户注册

**接口地址**: `POST /api/register`

**接口描述**: 用户注册

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 是 | 用户名 |
| email | string | 是 | 邮箱 |
| password | string | 是 | 密码 |
| confirm_password | string | 是 | 确认密码 |

**响应示例**:
```json
{
    "code": 201,
    "message": "注册成功",
    "data": {
        "id": 123,
        "username": "newuser",
        "email": "newuser@example.com",
        "created_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 获取当前用户信息

**接口地址**: `GET /api/me`

**接口描述**: 获取当前登录用户信息

**请求头**: 需要JWT认证

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "role_id": 1,
        "role_name": "超级管理员",
        "permissions": [
            "user.create",
            "user.update",
            "user.delete"
        ]
    },
    "timestamp": 1640995200
}
```

### 刷新令牌

**接口地址**: `POST /api/refresh-token`

**接口描述**: 刷新JWT令牌

**请求头**: 需要JWT认证

**响应示例**:
```json
{
    "code": 200,
    "message": "令牌刷新成功",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 86400
    },
    "timestamp": 1640995200
}
```

### 用户登出

**接口地址**: `POST /api/logout`

**接口描述**: 用户登出

**请求头**: 需要JWT认证

**响应示例**:
```json
{
    "code": 200,
    "message": "登出成功",
    "data": null,
    "timestamp": 1640995200
}
```

---

## 👥 用户管理

### 获取管理员列表

**接口地址**: `GET /api/admin`

**接口描述**: 获取管理员列表

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认1 |
| limit | integer | 否 | 每页数量，默认15 |
| search | string | 否 | 搜索关键词 |
| status | integer | 否 | 状态筛选 (1-正常, 0-禁用) |
| role_id | integer | 否 | 角色ID筛选 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "username": "admin",
            "email": "admin@example.com",
            "role_id": 1,
            "role_name": "超级管理员",
            "phone": "13800138000",
            "status": 1,
            "created_at": "2024-01-01 12:00:00",
            "updated_at": "2024-01-01 12:00:00"
        }
    ],
    "pagination": {
        "total": 10,
        "page": 1,
        "limit": 15,
        "pages": 1
    },
    "timestamp": 1640995200
}
```

### 创建管理员

**接口地址**: `POST /api/admin`

**接口描述**: 创建新管理员

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 是 | 用户名 |
| email | string | 是 | 邮箱 |
| password | string | 是 | 密码 |
| role_id | integer | 是 | 角色ID |
| phone | string | 否 | 手机号 |

**请求示例**:
```json
{
    "username": "newadmin",
    "email": "newadmin@example.com",
    "password": "password123",
    "role_id": 2,
    "phone": "13800138001"
}
```

**响应示例**:
```json
{
    "code": 201,
    "message": "管理员创建成功",
    "data": {
        "id": 2,
        "username": "newadmin",
        "email": "newadmin@example.com",
        "role_id": 2,
        "status": 1,
        "created_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 获取管理员详情

**接口地址**: `GET /api/admin/{id}`

**接口描述**: 获取指定管理员详情

**路径参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 管理员ID |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "role_id": 1,
        "role_name": "超级管理员",
        "phone": "13800138000",
        "status": 1,
        "created_at": "2024-01-01 12:00:00",
        "updated_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 更新管理员

**接口地址**: `PUT /api/admin/{id}`

**接口描述**: 更新管理员信息

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 否 | 用户名 |
| email | string | 否 | 邮箱 |
| role_id | integer | 否 | 角色ID |
| phone | string | 否 | 手机号 |

**响应示例**:
```json
{
    "code": 200,
    "message": "管理员更新成功",
    "data": {
        "id": 1,
        "username": "admin",
        "email": "admin@example.com",
        "role_id": 1,
        "phone": "13800138000",
        "updated_at": "2024-01-01 12:30:00"
    },
    "timestamp": 1640995200
}
```

### 删除管理员

**接口地址**: `DELETE /api/admin/{id}`

**接口描述**: 软删除管理员

**响应示例**:
```json
{
    "code": 200,
    "message": "管理员删除成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 重置密码

**接口地址**: `PUT /api/admin/{id}/reset-password`

**接口描述**: 重置管理员密码

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| password | string | 是 | 新密码 |

**响应示例**:
```json
{
    "code": 200,
    "message": "密码重置成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 获取管理员统计

**接口地址**: `GET /api/admin/stats`

**接口描述**: 获取管理员统计信息

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "total": 10,
        "active": 8,
        "inactive": 2,
        "today_created": 1,
        "role_stats": [
            {
                "role_id": 1,
                "role_name": "超级管理员",
                "count": 1
            }
        ]
    },
    "timestamp": 1640995200
}
```

---

## 🏷️ 角色管理

### 获取角色列表

**接口地址**: `GET /api/roles`

**接口描述**: 获取角色列表

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认1 |
| limit | integer | 否 | 每页数量，默认15 |
| search | string | 否 | 搜索关键词 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "role_name": "超级管理员",
            "description": "拥有所有权限",
            "order_no": 1,
            "rights_count": 25,
            "users_count": 1,
            "created_at": "2024-01-01 12:00:00"
        }
    ],
    "pagination": {
        "total": 5,
        "page": 1,
        "limit": 15,
        "pages": 1
    },
    "timestamp": 1640995200
}
```

### 创建角色

**接口地址**: `POST /api/roles`

**接口描述**: 创建新角色

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| role_name | string | 是 | 角色名称 |
| description | string | 否 | 角色描述 |
| order_no | integer | 否 | 排序号 |
| rights | array | 否 | 权限ID数组 |

**请求示例**:
```json
{
    "role_name": "编辑员",
    "description": "内容编辑权限",
    "order_no": 10,
    "rights": [1, 2, 3]
}
```

**响应示例**:
```json
{
    "code": 201,
    "message": "角色创建成功",
    "data": {
        "id": 3,
        "role_name": "编辑员",
        "description": "内容编辑权限",
        "order_no": 10,
        "created_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 获取角色详情

**接口地址**: `GET /api/roles/{id}`

**接口描述**: 获取指定角色详情

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "id": 1,
        "role_name": "超级管理员",
        "description": "拥有所有权限",
        "order_no": 1,
        "rights": [
            {
                "id": 1,
                "right_name": "用户管理",
                "description": "用户增删改查",
                "menu": 1
            }
        ],
        "created_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 更新角色

**接口地址**: `PUT /api/roles/{id}`

**接口描述**: 更新角色信息

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| role_name | string | 否 | 角色名称 |
| description | string | 否 | 角色描述 |
| order_no | integer | 否 | 排序号 |

**响应示例**:
```json
{
    "code": 200,
    "message": "角色更新成功",
    "data": {
        "id": 1,
        "role_name": "超级管理员",
        "description": "拥有所有权限",
        "order_no": 1,
        "updated_at": "2024-01-01 12:30:00"
    },
    "timestamp": 1640995200
}
```

### 删除角色

**接口地址**: `DELETE /api/roles/{id}`

**接口描述**: 软删除角色

**响应示例**:
```json
{
    "code": 200,
    "message": "角色删除成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 获取角色权限

**接口地址**: `GET /api/roles/{id}/rights`

**接口描述**: 获取角色拥有的权限

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "right_name": "用户管理",
            "description": "用户增删改查",
            "menu": 1,
            "sort": 1
        }
    ],
    "timestamp": 1640995200
}
```

### 设置角色权限

**接口地址**: `POST /api/roles/{id}/rights`

**接口描述**: 设置角色权限

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| rights | array | 是 | 权限ID数组 |

**请求示例**:
```json
{
    "rights": [1, 2, 3, 4, 5]
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "权限设置成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 获取权限树

**接口地址**: `GET /api/roles/all-rights-tree`

**接口描述**: 获取所有权限的树形结构

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "right_name": "系统管理",
            "description": "系统管理模块",
            "menu": 1,
            "children": [
                {
                    "id": 2,
                    "right_name": "用户管理",
                    "description": "用户增删改查",
                    "menu": 1,
                    "children": []
                }
            ]
        }
    ],
    "timestamp": 1640995200
}
```

---

## 🔒 权限管理

### 获取权限列表

**接口地址**: `GET /api/permissions`

**接口描述**: 获取权限列表

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认1 |
| limit | integer | 否 | 每页数量，默认15 |
| menu | integer | 否 | 是否菜单权限 (1-是, 0-否) |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "right_name": "用户管理",
            "description": "用户增删改查",
            "menu": 1,
            "sort": 1,
            "icon": "user",
            "created_at": "2024-01-01 12:00:00"
        }
    ],
    "pagination": {
        "total": 25,
        "page": 1,
        "limit": 15,
        "pages": 2
    },
    "timestamp": 1640995200
}
```

### 创建权限

**接口地址**: `POST /api/permissions`

**接口描述**: 创建新权限

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| pid | integer | 否 | 父级权限ID |
| right_name | string | 是 | 权限名称 |
| description | string | 否 | 权限描述 |
| menu | integer | 否 | 是否菜单 (1-是, 0-否) |
| sort | integer | 否 | 排序号 |
| icon | string | 否 | 图标 |

**请求示例**:
```json
{
    "pid": 0,
    "right_name": "商品管理",
    "description": "商品增删改查",
    "menu": 1,
    "sort": 10,
    "icon": "goods"
}
```

**响应示例**:
```json
{
    "code": 201,
    "message": "权限创建成功",
    "data": {
        "id": 26,
        "pid": 0,
        "right_name": "商品管理",
        "description": "商品增删改查",
        "menu": 1,
        "sort": 10,
        "icon": "goods",
        "created_at": "2024-01-01 12:00:00"
    },
    "timestamp": 1640995200
}
```

### 获取权限树

**接口地址**: `GET /api/permissions/tree`

**接口描述**: 获取权限树形结构

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "right_name": "系统管理",
            "description": "系统管理模块",
            "menu": 1,
            "children": [
                {
                    "id": 2,
                    "right_name": "用户管理",
                    "description": "用户增删改查",
                    "menu": 1,
                    "children": []
                }
            ]
        }
    ],
    "timestamp": 1640995200
}
```

### 获取菜单权限

**接口地址**: `GET /api/permissions/menu`

**接口描述**: 获取菜单权限列表

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "right_name": "系统管理",
            "description": "系统管理模块",
            "icon": "system",
            "sort": 1,
            "children": [
                {
                    "id": 2,
                    "right_name": "用户管理",
                    "description": "用户增删改查",
                    "icon": "user",
                    "sort": 1
                }
            ]
        }
    ],
    "timestamp": 1640995200
}
```

---

## 📊 操作日志

### 获取操作日志列表

**接口地址**: `GET /api/operation-logs`

**接口描述**: 获取操作日志列表

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认1 |
| limit | integer | 否 | 每页数量，默认15 |
| admin_id | integer | 否 | 管理员ID |
| operation_type | string | 否 | 操作类型 |
| start_time | string | 否 | 开始时间 (Y-m-d H:i:s) |
| end_time | string | 否 | 结束时间 (Y-m-d H:i:s) |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "admin_id": 1,
            "admin_name": "admin",
            "operation_type": "login",
            "operation_module": "auth",
            "operation_desc": "登录成功",
            "request_url": "/api/login",
            "request_method": "POST",
            "ip_address": "127.0.0.1",
            "status": 1,
            "operation_time": "2024-01-01 12:00:00"
        }
    ],
    "pagination": {
        "total": 100,
        "page": 1,
        "limit": 15,
        "pages": 7
    },
    "timestamp": 1640995200
}
```

### 获取日志统计

**接口地址**: `GET /api/operation-logs/stats`

**接口描述**: 获取操作日志统计信息

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| start_time | string | 否 | 开始时间 |
| end_time | string | 否 | 结束时间 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "total_operations": 1000,
        "success_operations": 950,
        "failed_operations": 50,
        "today_operations": 50,
        "type_stats": [
            {
                "operation_type": "login",
                "count": 100
            }
        ],
        "module_stats": [
            {
                "operation_module": "auth",
                "count": 150
            }
        ]
    },
    "timestamp": 1640995200
}
```

### 清理日志

**接口地址**: `POST /api/operation-logs/cleanup`

**接口描述**: 清理过期日志

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| days | integer | 是 | 保留天数 |

**请求示例**:
```json
{
    "days": 30
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "日志清理成功",
    "data": {
        "deleted_count": 500
    },
    "timestamp": 1640995200
}
```

---

## ⚡ 性能监控

### 获取性能统计

**接口地址**: `GET /api/performance/stats`

**接口描述**: 获取性能统计信息

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| days | integer | 否 | 统计天数，默认7 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "total_requests": 10000,
        "avg_response_time": 150.5,
        "max_response_time": 2000.0,
        "avg_memory_usage": 1024.5,
        "max_memory_usage": 2048.0,
        "error_count": 50,
        "active_users": 100,
        "unique_ips": 50
    },
    "timestamp": 1640995200
}
```

### 获取慢查询

**接口地址**: `GET /api/performance/slow-queries`

**接口描述**: 获取慢查询列表

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| threshold | integer | 否 | 慢查询阈值(毫秒)，默认1000 |
| limit | integer | 否 | 返回数量，默认20 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": {
        "slow_queries": [
            {
                "endpoint": "/api/users",
                "method": "GET",
                "response_time": 1500.0,
                "memory_usage": 2048.0,
                "created_at": "2024-01-01 12:00:00"
            }
        ],
        "threshold": 1000,
        "count": 5
    },
    "timestamp": 1640995200
}
```

---

## 🗑️ 软删除管理

### 获取已删除数据列表

**接口地址**: `GET /api/soft-delete/{type}`

**接口描述**: 获取已软删除的数据列表

**路径参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| type | string | 是 | 数据类型 (admins/users/rights/logs) |

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码，默认1 |
| limit | integer | 否 | 每页数量，默认15 |

**响应示例**:
```json
{
    "code": 200,
    "message": "获取成功",
    "data": [
        {
            "id": 1,
            "username": "deleted_user",
            "email": "deleted@example.com",
            "delete_time": "2024-01-01 12:00:00"
        }
    ],
    "pagination": {
        "total": 5,
        "page": 1,
        "limit": 15,
        "pages": 1
    },
    "timestamp": 1640995200
}
```

### 恢复数据

**接口地址**: `POST /api/soft-delete/restore/{type}`

**接口描述**: 恢复软删除的数据

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据ID |

**请求示例**:
```json
{
    "id": 1
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "恢复成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 永久删除数据

**接口地址**: `POST /api/soft-delete/force-delete/{type}`

**接口描述**: 永久删除数据

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 数据ID |

**响应示例**:
```json
{
    "code": 200,
    "message": "永久删除成功",
    "data": null,
    "timestamp": 1640995200
}
```

### 清理软删除数据

**接口地址**: `POST /api/soft-delete/cleanup`

**接口描述**: 清理过期的软删除数据

**请求参数**:

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| days | integer | 是 | 清理天数 |

**请求示例**:
```json
{
    "days": 7
}
```

**响应示例**:
```json
{
    "code": 200,
    "message": "清理完成",
    "data": {
        "log_cleaned": 10,
        "performance_cleaned": 5,
        "days": 7
    },
    "timestamp": 1640995200
}
```

---

## 📞 技术支持

### 联系方式

- **项目仓库**: [GitHub Repository]
- **技术文档**: [Documentation]
- **问题反馈**: [Issues]

### 更新日志

详细的更新日志请参考 [CHANGELOG.md](./CHANGELOG.md)

---

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](./LICENSE) 文件了解详情。

---

*最后更新: 2024-01-01*