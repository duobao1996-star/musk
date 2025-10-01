# RBAC权限控制系统设计文档

## 🎯 系统概述

基于Webman框架设计的RBAC（Role-Based Access Control）权限控制系统，采用中间件架构实现统一的权限验证。

## 🏗️ 架构设计

### 权限控制流程
```
请求 → JWT中间件 → 权限中间件 → 控制器 → 响应
```

### 中间件职责分工
- **JWT中间件**：验证用户身份，设置`request->user`
- **权限中间件**：验证用户权限，拦截无权限请求
- **控制器**：专注业务逻辑，无需关心权限验证

## 📋 权限配置

### 权限模块划分
```php
private array $permissions = [
    // 角色管理模块
    'roles' => [
        'permissions' => [231, 232, 233, 234],
        'routes' => [
            'GET:/api/roles' => 231,           // 角色列表
            'POST:/api/roles' => 232,          // 角色添加
            'PUT:/api/roles/{id}' => 233,      // 角色编辑
            'DELETE:/api/roles/{id}' => 233,   // 角色删除
            'GET:/api/roles/{id}/rights' => 234,    // 角色权限查看
            'POST:/api/roles/{id}/rights' => 234,   // 角色权限设置
        ]
    ],
    
    // 管理员管理模块
    'admins' => [
        'permissions' => [241, 242, 243],
        'routes' => [
            'GET:/api/admins' => 241,          // 管理员列表
            'POST:/api/admins' => 242,         // 管理员添加
            'PUT:/api/admins/{id}' => 243,     // 管理员编辑
            'DELETE:/api/admins/{id}' => 243,  // 管理员删除
        ]
    ],
    
    // 权限管理模块
    'permissions' => [
        'permissions' => [251, 252, 253],
        'routes' => [
            'GET:/api/permissions' => 251,     // 权限列表
            'POST:/api/permissions' => 252,    // 权限添加
            'PUT:/api/permissions/{id}' => 253, // 权限编辑
            'DELETE:/api/permissions/{id}' => 253, // 权限删除
        ]
    ],
    
    // 操作日志模块
    'logs' => [
        'permissions' => [21],
        'routes' => [
            'GET:/api/operation-logs' => 21,   // 日志查看
        ]
    ],
    
    // 性能监控模块
    'performance' => [
        'permissions' => [22],
        'routes' => [
            'GET:/api/performance/stats' => 22,    // 性能统计
            'GET:/api/performance/slow-queries' => 22, // 慢查询
        ]
    ]
];
```

### 公开路由配置
```php
private array $publicRoutes = [
    'POST:/api/login',
    'POST:/api/logout',
    'GET:/api/me',
    'GET:/api/permissions/menu',
    'GET:/api/roles/all-rights-tree',
];
```

## 🔧 技术特性

### 1. 模块化设计
- 按功能模块划分权限配置
- 支持权限组管理
- 易于扩展和维护

### 2. 动态路由支持
- 支持`{id}`参数匹配
- 自动转换为正则表达式
- 灵活的权限映射

### 3. 超级管理员支持
- 自动识别超级管理员角色
- 超级管理员拥有所有权限
- 安全的权限检查机制

### 4. 错误处理
- 统一的错误响应格式
- 详细的权限验证日志
- 优雅的异常处理

## 📊 数据库设计

### 核心表结构
```sql
-- 管理员表
pay_admin (id, user_name, user_password, email, role_id, status)

-- 角色表
pay_role (id, role_name, description, is_del)

-- 权限表
pay_right (id, right_name, description, pid, is_menu)

-- 管理员角色关联表
pay_admin_role (admin_id, role_id)

-- 角色权限关联表
pay_role_right (role_id, right_id)
```

### 权限ID映射
- 21: 操作日志查看
- 22: 性能监控查看
- 231: 角色列表
- 232: 角色添加
- 233: 角色编辑/删除
- 234: 角色权限设置
- 241: 管理员列表
- 242: 管理员添加
- 243: 管理员编辑/删除
- 251: 权限列表
- 252: 权限添加
- 253: 权限编辑/删除

## 🚀 使用示例

### 路由配置
```php
// 公开路由（无需权限验证）
Route::post('/api/login', [AuthController::class, 'login']);

// 需要权限验证的管理接口
Route::get('/api/roles', [RoleController::class, 'index'])->middleware([
    JwtMiddleware::class,
    PermissionMiddleware::class
]);
```

### 权限检查
```php
// 中间件自动处理权限验证
// 控制器无需关心权限逻辑
public function index(Request $request): Response
{
    // 直接处理业务逻辑
    return $this->success($data);
}
```

## ✅ 优势特点

### 1. 代码简洁
- 控制器无需重复权限检查代码
- 统一的权限验证逻辑
- 易于维护和扩展

### 2. 性能优化
- 中间件级别的权限验证
- 减少重复的数据库查询
- 高效的权限检查机制

### 3. 安全性
- 统一的权限控制入口
- 防止权限绕过
- 完整的审计日志

### 4. 可扩展性
- 模块化的权限配置
- 支持新的权限类型
- 灵活的权限映射

## 🔍 测试验证

### Viewer用户权限测试
```bash
# 有权限的操作
curl -X GET /api/roles -H "Authorization: Bearer <token>"  # 200 OK

# 无权限的操作
curl -X POST /api/roles -H "Authorization: Bearer <token>"  # 403 Forbidden
```

## 📝 总结

本RBAC权限控制系统采用Webman框架的中间件特性，实现了：

- ✅ **统一权限控制**：所有API权限验证统一处理
- ✅ **模块化设计**：按功能模块划分权限配置
- ✅ **高性能**：中间件级别的权限验证
- ✅ **易维护**：清晰的代码结构和配置
- ✅ **高安全性**：完整的权限验证机制

系统设计合理，符合Webman框架特性，具有良好的扩展性和维护性。
