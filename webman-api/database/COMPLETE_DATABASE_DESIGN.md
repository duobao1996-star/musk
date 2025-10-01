# 🗄️ 完整数据库设计文档

## 📋 数据库架构概览

### 核心表结构
```
pay_admin (管理员表)
├── pay_admin_role (管理员角色关联表)
├── pay_admin_token (管理员令牌表)
│
pay_role (角色表)
├── pay_role_right (角色权限关联表)
│
pay_right (权限表)
│
pay_permission_middleware (权限中间件配置表)
│
pay_permission_cache (权限缓存表)
│
pay_operation_log (操作日志表)
```

## 🏗️ 详细表结构设计

### 1. 管理员表 (pay_admin)
```sql
CREATE TABLE `pay_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `user_password` varchar(255) NOT NULL COMMENT '密码(Argon2ID)',
  `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL',
  `role_id` int(11) DEFAULT 1 COMMENT '角色ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=正常，0=禁用',
  `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `login_count` int(11) DEFAULT 0 COMMENT '登录次数',
  `admin_code` int(11) DEFAULT 0 COMMENT '管理员编码',
  `sgin` varchar(100) DEFAULT NULL COMMENT '签名',
  `login_id` varchar(50) DEFAULT NULL COMMENT '登录ID',
  `Remarks` text DEFAULT NULL COMMENT '备注',
  `user_password_old` varchar(255) DEFAULT NULL COMMENT '旧密码',
  `current_token` varchar(500) DEFAULT NULL COMMENT '当前令牌',
  `token_expires_at` timestamp NULL DEFAULT NULL COMMENT '令牌过期时间',
  `token_created_at` timestamp NULL DEFAULT NULL COMMENT '令牌创建时间',
  `ctime` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `etime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_name` (`user_name`),
  UNIQUE KEY `uk_email` (`email`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_login` (`last_login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';
```

### 2. 角色表 (pay_role)
```sql
CREATE TABLE `pay_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(120) NOT NULL COMMENT '角色名称',
  `description` varchar(255) DEFAULT '' COMMENT '角色描述',
  `order_no` int(11) NOT NULL DEFAULT 0 COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `is_del` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否删除：1=正常，0=删除',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `modify_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_name` (`role_name`),
  KEY `idx_order_no` (`order_no`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';
```

### 3. 权限表 (pay_right)
```sql
CREATE TABLE `pay_right` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT 0 COMMENT '父ID，根为0',
  `right_name` varchar(120) NOT NULL COMMENT '权限编码(唯一)',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '权限名称/描述',
  `path` varchar(255) DEFAULT NULL COMMENT '路由或API路径',
  `method` varchar(10) DEFAULT 'GET' COMMENT 'HTTP方法',
  `is_menu` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否菜单：1=是，0=否',
  `icon` varchar(120) DEFAULT NULL COMMENT '图标',
  `component` varchar(255) DEFAULT NULL COMMENT '前端组件路径',
  `redirect` varchar(255) DEFAULT NULL COMMENT '重定向路径',
  `hidden` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否隐藏：1=隐藏，0=显示',
  `always_show` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否总是显示',
  `no_cache` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否缓存：1=不缓存，0=缓存',
  `affix` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否固定标签',
  `breadcrumb` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否显示面包屑',
  `active_menu` varchar(255) DEFAULT NULL COMMENT '激活菜单',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序号',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `is_del` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否删除：1=正常，0=删除',
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `modify_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_right_name` (`right_name`),
  KEY `idx_pid_sort` (`pid`,`sort`),
  KEY `idx_path_method` (`path`,`method`),
  KEY `idx_is_menu` (`is_menu`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';
```

### 4. 角色权限关联表 (pay_role_right)
```sql
CREATE TABLE `pay_role_right` (
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `right_id` int(11) NOT NULL COMMENT '权限ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  UNIQUE KEY `uk_role_right` (`role_id`,`right_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_right_id` (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';
```

### 5. 管理员角色关联表 (pay_admin_role)
```sql
CREATE TABLE `pay_admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_role` (`admin_id`,`role_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色关联表';
```

### 6. 管理员令牌表 (pay_admin_token)
```sql
CREATE TABLE `pay_admin_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `token` varchar(500) NOT NULL COMMENT 'JWT令牌',
  `refresh_token` varchar(500) DEFAULT NULL COMMENT '刷新令牌',
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `refresh_expires_at` timestamp DEFAULT NULL COMMENT '刷新令牌过期时间',
  `device_info` varchar(255) DEFAULT NULL COMMENT '设备信息',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` text DEFAULT NULL COMMENT '用户代理',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否激活：1=是，0=否',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员令牌表';
```

### 7. 权限中间件配置表 (pay_permission_middleware)
```sql
CREATE TABLE `pay_permission_middleware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '中间件名称',
  `path_pattern` varchar(255) NOT NULL COMMENT '路径匹配模式',
  `method` varchar(10) DEFAULT 'ALL' COMMENT 'HTTP方法',
  `permission_id` int(11) DEFAULT NULL COMMENT '关联权限ID',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '是否公开接口：1=是，0=否',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_path_pattern` (`path_pattern`),
  KEY `idx_permission_id` (`permission_id`),
  KEY `idx_is_public` (`is_public`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限中间件配置表';
```

### 8. 权限缓存表 (pay_permission_cache)
```sql
CREATE TABLE `pay_permission_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `permissions` mediumtext NOT NULL COMMENT '权限JSON',
  `menus` mediumtext NOT NULL COMMENT '菜单JSON',
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限缓存表';
```

### 9. 操作日志表 (pay_operation_log)
```sql
CREATE TABLE `pay_operation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
  `admin_name` varchar(50) DEFAULT NULL COMMENT '管理员用户名',
  `operation_type` varchar(50) NOT NULL COMMENT '操作类型',
  `operation_module` varchar(100) DEFAULT NULL COMMENT '操作模块',
  `operation_description` varchar(255) DEFAULT NULL COMMENT '操作描述',
  `request_method` varchar(10) DEFAULT NULL COMMENT '请求方法',
  `request_url` varchar(255) DEFAULT NULL COMMENT '请求URL',
  `request_params` text DEFAULT NULL COMMENT '请求参数',
  `request_ip` varchar(45) DEFAULT NULL COMMENT '请求IP',
  `user_agent` text DEFAULT NULL COMMENT '用户代理',
  `response_code` int(11) DEFAULT NULL COMMENT '响应状态码',
  `response_data` text DEFAULT NULL COMMENT '响应数据',
  `execution_time` int(11) DEFAULT NULL COMMENT '执行时间(毫秒)',
  `error_message` text DEFAULT NULL COMMENT '错误信息',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_operation_type` (`operation_type`),
  KEY `idx_operation_module` (`operation_module`),
  KEY `idx_request_ip` (`request_ip`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_response_code` (`response_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';
```

## 🔐 权限系统设计

### 权限层级结构
```
1. 仪表盘 (dashboard)
   ├── 仪表盘首页 (dashboard.index)

2. 系统管理 (system)
   ├── 系统管理 (system.management)
   │   ├── 系统信息 (system.management.info)
   │   └── 系统配置 (system.management.config)
   ├── 性能监控 (system.performance)
   │   ├── 性能状态 (system.performance.status)
   │   ├── 慢查询 (system.performance.slow)
   │   └── 性能趋势 (system.performance.trends)

3. 管理员管理 (admin)
   ├── 操作日志 (admin.logs)
   │   ├── 日志列表 (admin.logs.list)
   │   ├── 日志统计 (admin.logs.stats)
   │   └── 日志清理 (admin.logs.clean)
   ├── 权限管理 (admin.permissions)
   │   ├── 权限列表 (admin.permissions.list)
   │   ├── 权限添加 (admin.permissions.add)
   │   └── 权限编辑 (admin.permissions.edit)
   ├── 角色管理 (admin.roles)
   │   ├── 角色列表 (admin.roles.list)
   │   ├── 角色添加 (admin.roles.add)
   │   ├── 角色编辑 (admin.roles.edit)
   │   └── 角色权限 (admin.roles.permissions)
   └── 管理员账号 (admin.accounts)
       ├── 管理员列表 (admin.accounts.list)
       ├── 管理员添加 (admin.accounts.add)
       └── 管理员编辑 (admin.accounts.edit)

4. API权限 (api)
   ├── 权限API (api.permissions.*)
   ├── 角色API (api.roles.*)
   ├── 日志API (api.logs.*)
   ├── 性能API (api.performance.*)
   └── 管理员API (api.admins.*)
```

### 角色权限配置
```sql
-- 超级管理员：拥有所有权限
-- Admin：拥有管理权限（除超级管理员功能）
-- Operator：拥有操作权限（日志查看、性能监控）
-- Viewer：只读权限（仪表盘、系统查看、日志查看）
```

## 📊 数据完整性约束

### 外键约束
```sql
-- 管理员角色关联
ALTER TABLE `pay_admin_role` 
ADD CONSTRAINT `fk_admin_role_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_admin_role_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE;

-- 角色权限关联
ALTER TABLE `pay_role_right` 
ADD CONSTRAINT `fk_role_right_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_role_right_right` FOREIGN KEY (`right_id`) REFERENCES `pay_right`(`id`) ON DELETE CASCADE;

-- 管理员令牌关联
ALTER TABLE `pay_admin_token` 
ADD CONSTRAINT `fk_admin_token_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE;

-- 权限中间件关联
ALTER TABLE `pay_permission_middleware` 
ADD CONSTRAINT `fk_middleware_permission` FOREIGN KEY (`permission_id`) REFERENCES `pay_right`(`id`) ON DELETE SET NULL;

-- 权限缓存关联
ALTER TABLE `pay_permission_cache` 
ADD CONSTRAINT `fk_cache_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE;

-- 操作日志关联
ALTER TABLE `pay_operation_log` 
ADD CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE SET NULL;
```

## 🔍 索引优化策略

### 查询优化索引
```sql
-- 复合索引优化常用查询
CREATE INDEX `idx_admin_role_status` ON `pay_admin` (`role_id`, `status`);
CREATE INDEX `idx_right_pid_menu_sort` ON `pay_right` (`pid`, `is_menu`, `sort`);
CREATE INDEX `idx_log_admin_created` ON `pay_operation_log` (`admin_id`, `created_at`);
CREATE INDEX `idx_token_admin_active` ON `pay_admin_token` (`admin_id`, `is_active`, `expires_at`);

-- 全文索引（如果需要搜索功能）
ALTER TABLE `pay_right` ADD FULLTEXT(`description`);
ALTER TABLE `pay_operation_log` ADD FULLTEXT(`operation_description`);
```

## 🚀 性能优化建议

### 1. 查询优化
- 使用适当的索引
- 避免全表扫描
- 合理使用缓存
- 分页查询优化

### 2. 缓存策略
- 权限缓存：5分钟过期
- 菜单缓存：10分钟过期
- 用户信息缓存：30分钟过期

### 3. 数据清理
- 操作日志定期清理（保留30天）
- 过期令牌定期清理
- 权限缓存定期清理

## 🔒 安全考虑

### 1. 密码安全
- 使用Argon2ID算法
- 密码复杂度要求
- 定期密码更新

### 2. 令牌安全
- JWT令牌过期时间控制
- 刷新令牌机制
- 设备绑定验证

### 3. 权限控制
- 最小权限原则
- 权限继承机制
- 操作日志审计

### 4. 数据保护
- 敏感数据加密
- SQL注入防护
- XSS攻击防护
