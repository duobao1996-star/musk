-- ========================================
-- 完整数据库设置脚本
-- 版本: 1.0.0
-- 描述: 创建完整的RBAC权限管理系统数据库
-- ========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- 1. 删除现有表（如果存在）
-- ========================================
DROP TABLE IF EXISTS `pay_operation_log`;
DROP TABLE IF EXISTS `pay_permission_cache`;
DROP TABLE IF EXISTS `pay_permission_middleware`;
DROP TABLE IF EXISTS `pay_admin_role`;
DROP TABLE IF EXISTS `pay_admin_token`;
DROP TABLE IF EXISTS `pay_role_right`;
DROP TABLE IF EXISTS `pay_role`;
DROP TABLE IF EXISTS `pay_right`;
DROP TABLE IF EXISTS `pay_admin`;

-- ========================================
-- 2. 创建管理员表
-- ========================================
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

-- ========================================
-- 3. 创建角色表
-- ========================================
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

-- ========================================
-- 4. 创建权限表
-- ========================================
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

-- ========================================
-- 5. 创建角色权限关联表
-- ========================================
CREATE TABLE `pay_role_right` (
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `right_id` int(11) NOT NULL COMMENT '权限ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  UNIQUE KEY `uk_role_right` (`role_id`,`right_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_right_id` (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- ========================================
-- 6. 创建管理员角色关联表
-- ========================================
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

-- ========================================
-- 7. 创建管理员令牌表
-- ========================================
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

-- ========================================
-- 8. 创建权限中间件配置表
-- ========================================
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

-- ========================================
-- 9. 创建权限缓存表
-- ========================================
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

-- ========================================
-- 10. 创建操作日志表
-- ========================================
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

-- ========================================
-- 11. 插入基础角色数据
-- ========================================
INSERT INTO `pay_role` (`id`, `role_name`, `description`, `order_no`, `status`, `is_del`) VALUES
(1, '超级管理员', '拥有所有权限的超级管理员角色', 1, 1, 1),
(2, 'Admin', '普通管理员，拥有管理权限', 2, 1, 1),
(3, 'Operator', '操作员，拥有操作权限', 3, 1, 1),
(4, 'Viewer', '只读用户，仅查看权限', 4, 1, 1);

-- ========================================
-- 12. 插入完整权限数据
-- ========================================
-- 仪表盘
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(1, 0, 'dashboard', '仪表盘', '/', 'GET', 1, 'ri:dashboard-line', 'DashboardView', NULL, 0, 1, 0, 1, 1, NULL, 100, 1, 1);

-- 系统管理分组
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(10, 0, 'system', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'Layout', '/system/management', 0, 1, 0, 0, 1, NULL, 200, 1, 1),
(11, 10, 'system.management', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'system/SystemView', NULL, 0, 1, 0, 0, 1, NULL, 210, 1, 1),
(12, 10, 'system.performance', '性能监控', '/system/performance', 'GET', 1, 'ri:line-chart-line', 'system/PerformanceView', NULL, 0, 1, 0, 0, 1, NULL, 220, 1, 1);

-- 系统管理子项
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(111, 11, 'system.management.info', '系统信息', '/system/info', 'GET', 1, 'ri:information-line', 'system/SystemInfoView', NULL, 0, 1, 0, 0, 1, NULL, 211, 1, 1),
(112, 11, 'system.management.config', '系统配置', '/system/config', 'GET', 1, 'ri:settings-3-line', 'system/SystemConfigView', NULL, 0, 1, 0, 0, 1, NULL, 212, 1, 1),
(121, 12, 'system.performance.status', '性能状态', '/system/performance', 'GET', 1, 'ri:dashboard-line', 'system/PerformanceView', NULL, 0, 1, 0, 0, 1, NULL, 221, 1, 1),
(122, 12, 'system.performance.slow', '慢查询', '/system/performance/slow', 'GET', 1, 'ri:speed-line', 'system/PerformanceSlowQueryView', NULL, 0, 1, 0, 0, 1, NULL, 222, 1, 1),
(123, 12, 'system.performance.trends', '性能趋势', '/system/performance/trends', 'GET', 1, 'ri:bar-chart-line', 'system/PerformanceTrendsView', NULL, 0, 1, 0, 0, 1, NULL, 223, 1, 1);

-- 管理员管理分组
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(20, 0, 'admin', '管理员管理', '/admin', 'GET', 1, 'ri:admin-line', 'Layout', '/system/logs', 0, 1, 0, 0, 1, NULL, 300, 1, 1),
(21, 20, 'admin.logs', '操作日志', '/system/logs', 'GET', 1, 'ri:file-list-line', 'system/LogView', NULL, 0, 1, 0, 0, 1, NULL, 310, 1, 1),
(22, 20, 'admin.permissions', '权限管理', '/system/permissions', 'GET', 1, 'ri:key-line', 'system/PermissionView', NULL, 0, 1, 0, 0, 1, NULL, 320, 1, 1),
(23, 20, 'admin.roles', '角色管理', '/system/roles', 'GET', 1, 'ri:user-settings-line', 'system/RoleView', NULL, 0, 1, 0, 0, 1, NULL, 330, 1, 1),
(24, 20, 'admin.accounts', '管理员账号', '/system/admins', 'GET', 1, 'ri:user-line', 'system/AdminView', NULL, 0, 1, 0, 0, 1, NULL, 340, 1, 1);

-- 管理员管理子项
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(211, 21, 'admin.logs.list', '日志列表', '/system/logs', 'GET', 1, 'ri:list-check', 'system/LogView', NULL, 0, 1, 0, 0, 1, NULL, 311, 1, 1),
(212, 21, 'admin.logs.stats', '日志统计', '/system/logs/stats', 'GET', 1, 'ri:pie-chart-line', 'system/LogStatsView', NULL, 0, 1, 0, 0, 1, NULL, 312, 1, 1),
(213, 21, 'admin.logs.clean', '日志清理', '/system/logs/clean', 'POST', 1, 'ri:delete-bin-line', 'system/LogCleanView', NULL, 0, 1, 0, 0, 1, NULL, 313, 1, 1),
(221, 22, 'admin.permissions.list', '权限列表', '/system/permissions', 'GET', 1, 'ri:list-check', 'system/PermissionView', NULL, 0, 1, 0, 0, 1, NULL, 321, 1, 1),
(222, 22, 'admin.permissions.add', '权限添加', '/system/permissions/add', 'POST', 1, 'ri:add-line', 'system/PermissionAddView', NULL, 0, 1, 0, 0, 1, NULL, 322, 1, 1),
(223, 22, 'admin.permissions.edit', '权限编辑', '/system/permissions/edit', 'PUT', 1, 'ri:edit-line', 'system/PermissionEditView', NULL, 0, 1, 0, 0, 1, NULL, 323, 1, 1),
(231, 23, 'admin.roles.list', '角色列表', '/system/roles', 'GET', 1, 'ri:list-check', 'system/RoleView', NULL, 0, 1, 0, 0, 1, NULL, 331, 1, 1),
(232, 23, 'admin.roles.add', '角色添加', '/system/roles/add', 'POST', 1, 'ri:add-line', 'system/RoleAddView', NULL, 0, 1, 0, 0, 1, NULL, 332, 1, 1),
(233, 23, 'admin.roles.edit', '角色编辑', '/system/roles/edit', 'PUT', 1, 'ri:edit-line', 'system/RoleEditView', NULL, 0, 1, 0, 0, 1, NULL, 333, 1, 1),
(234, 23, 'admin.roles.permissions', '角色权限', '/system/roles/permissions', 'POST', 1, 'ri:shield-keyhole-line', 'system/RolePermissionView', NULL, 0, 1, 0, 0, 1, NULL, 334, 1, 1),
(241, 24, 'admin.accounts.list', '管理员列表', '/system/admins', 'GET', 1, 'ri:list-check', 'system/AdminView', NULL, 0, 1, 0, 0, 1, NULL, 341, 1, 1),
(242, 24, 'admin.accounts.add', '管理员添加', '/system/admins/add', 'POST', 1, 'ri:user-add-line', 'system/AdminAddView', NULL, 0, 1, 0, 0, 1, NULL, 342, 1, 1),
(243, 24, 'admin.accounts.edit', '管理员编辑', '/system/admins/edit', 'PUT', 1, 'ri:user-edit-line', 'system/AdminEditView', NULL, 0, 1, 0, 0, 1, NULL, 343, 1, 1);

-- API权限（非菜单）
INSERT INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(100, 0, 'api', 'API权限', NULL, 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 0, 1, 1),
(101, 100, 'api.permissions.list', '获取权限列表', '/api/permissions', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 1, 1, 1),
(102, 100, 'api.permissions.create', '创建权限', '/api/permissions', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 2, 1, 1),
(103, 100, 'api.permissions.update', '更新权限', '/api/permissions/{id}', 'PUT', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 3, 1, 1),
(104, 100, 'api.permissions.delete', '删除权限', '/api/permissions/{id}', 'DELETE', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 4, 1, 1),
(105, 100, 'api.permissions.tree', '获取权限树', '/api/permissions/tree', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 5, 1, 1),
(106, 100, 'api.permissions.menu', '获取菜单权限', '/api/permissions/menu', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 6, 1, 1),
(111, 100, 'api.roles.list', '获取角色列表', '/api/roles', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 7, 1, 1),
(112, 100, 'api.roles.create', '创建角色', '/api/roles', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 8, 1, 1),
(113, 100, 'api.roles.update', '更新角色', '/api/roles/{id}', 'PUT', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 9, 1, 1),
(114, 100, 'api.roles.delete', '删除角色', '/api/roles/{id}', 'DELETE', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 10, 1, 1),
(115, 100, 'api.roles.rights', '获取角色权限', '/api/roles/{id}/rights', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 11, 1, 1),
(116, 100, 'api.roles.set-rights', '设置角色权限', '/api/roles/{id}/rights', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 12, 1, 1),
(121, 100, 'api.logs.list', '获取操作日志', '/api/operation-logs', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 13, 1, 1),
(122, 100, 'api.logs.stats', '获取日志统计', '/api/operation-logs/stats', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 14, 1, 1),
(123, 100, 'api.logs.clean', '清理日志', '/api/operation-logs/clean', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 15, 1, 1),
(131, 100, 'api.performance.status', '获取性能状态', '/api/performance/status', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 16, 1, 1),
(132, 100, 'api.performance.slow-queries', '获取慢查询', '/api/performance/slow-queries', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 17, 1, 1),
(133, 100, 'api.performance.trends', '获取性能趋势', '/api/performance/trends', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 18, 1, 1),
(141, 100, 'api.admins.list', '获取管理员列表', '/api/admins', 'GET', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 19, 1, 1),
(142, 100, 'api.admins.create', '创建管理员', '/api/admins', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 20, 1, 1),
(143, 100, 'api.admins.update', '更新管理员', '/api/admins/{id}', 'PUT', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 21, 1, 1),
(144, 100, 'api.admins.delete', '删除管理员', '/api/admins/{id}', 'DELETE', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 22, 1, 1),
(145, 100, 'api.admins.reset-password', '重置密码', '/api/admins/{id}/reset-password', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 23, 1, 1),
(146, 100, 'api.admins.toggle-status', '切换状态', '/api/admins/{id}/toggle-status', 'POST', 0, NULL, NULL, NULL, 1, 1, 0, 0, 1, NULL, 24, 1, 1);

-- ========================================
-- 13. 插入权限中间件配置
-- ========================================
INSERT INTO `pay_permission_middleware` (`name`, `path_pattern`, `method`, `permission_id`, `is_public`, `description`, `status`) VALUES
-- 公开接口
('登录接口', '/api/login', 'POST', NULL, 1, '用户登录接口', 1),
('登出接口', '/api/logout', 'POST', NULL, 1, '用户登出接口', 1),
('刷新令牌', '/api/refresh-token', 'POST', NULL, 1, '刷新JWT令牌', 1),
('获取用户信息', '/api/me', 'GET', NULL, 1, '获取当前用户信息', 1),
('健康检查', '/api/health', 'GET', NULL, 1, '健康检查接口', 1),
('API文档', '/api-docs', 'GET', NULL, 1, 'API文档页面', 1),
-- 权限相关接口
('权限列表', '/api/permissions', 'GET', 101, 0, '获取权限列表', 1),
('创建权限', '/api/permissions', 'POST', 102, 0, '创建权限', 1),
('更新权限', '/api/permissions/*', 'PUT', 103, 0, '更新权限', 1),
('删除权限', '/api/permissions/*', 'DELETE', 104, 0, '删除权限', 1),
('权限树', '/api/permissions/tree', 'GET', 105, 0, '获取权限树', 1),
('菜单权限', '/api/permissions/menu', 'GET', 106, 0, '获取菜单权限', 1),
-- 角色相关接口
('角色列表', '/api/roles', 'GET', 111, 0, '获取角色列表', 1),
('创建角色', '/api/roles', 'POST', 112, 0, '创建角色', 1),
('更新角色', '/api/roles/*', 'PUT', 113, 0, '更新角色', 1),
('删除角色', '/api/roles/*', 'DELETE', 114, 0, '删除角色', 1),
('角色权限', '/api/roles/*/rights', 'GET', 115, 0, '获取角色权限', 1),
('设置角色权限', '/api/roles/*/rights', 'POST', 116, 0, '设置角色权限', 1),
-- 日志相关接口
('操作日志', '/api/operation-logs', 'GET', 121, 0, '获取操作日志', 1),
('日志统计', '/api/operation-logs/stats', 'GET', 122, 0, '获取日志统计', 1),
('清理日志', '/api/operation-logs/clean', 'POST', 123, 0, '清理日志', 1),
-- 性能相关接口
('性能状态', '/api/performance/status', 'GET', 131, 0, '获取性能状态', 1),
('慢查询', '/api/performance/slow-queries', 'GET', 132, 0, '获取慢查询', 1),
('性能趋势', '/api/performance/trends', 'GET', 133, 0, '获取性能趋势', 1),
-- 管理员相关接口
('管理员列表', '/api/admins', 'GET', 141, 0, '获取管理员列表', 1),
('创建管理员', '/api/admins', 'POST', 142, 0, '创建管理员', 1),
('更新管理员', '/api/admins/*', 'PUT', 143, 0, '更新管理员', 1),
('删除管理员', '/api/admins/*', 'DELETE', 144, 0, '删除管理员', 1),
('重置密码', '/api/admins/*/reset-password', 'POST', 145, 0, '重置密码', 1),
('切换状态', '/api/admins/*/toggle-status', 'POST', 146, 0, '切换状态', 1);

-- ========================================
-- 14. 为超级管理员分配所有权限
-- ========================================
INSERT INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, id FROM `pay_right` WHERE `is_del` = 1;

-- ========================================
-- 15. 插入默认管理员账号
-- ========================================
INSERT INTO `pay_admin` (`id`, `user_name`, `email`, `user_password`, `role_id`, `status`, `ctime`, `etime`) VALUES
(1, 'admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 1, 1, NOW(), NOW()),
(2, 'manager', 'manager@example.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 2, 1, NOW(), NOW()),
(3, 'operator', 'operator@example.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 3, 1, NOW(), NOW()),
(4, 'viewer', 'viewer@example.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 4, 1, NOW(), NOW());

-- ========================================
-- 16. 为管理员分配角色
-- ========================================
INSERT INTO `pay_admin_role` (`admin_id`, `role_id`) VALUES
(1, 1), -- admin -> 超级管理员
(2, 2), -- manager -> Admin
(3, 3), -- operator -> Operator
(4, 4); -- viewer -> Viewer

-- ========================================
-- 17. 为Viewer角色分配只读权限
-- ========================================
INSERT INTO `pay_role_right` (`role_id`, `right_id`) VALUES
-- 仪表盘
(4, 1),
-- 系统管理
(4, 10), (4, 11), (4, 111), (4, 112), (4, 12), (4, 121), (4, 122), (4, 123),
-- 操作日志（只读）
(4, 20), (4, 21), (4, 211), (4, 212),
-- 相关API权限
(4, 101), (4, 105), (4, 106), (4, 111), (4, 115), (4, 121), (4, 122), (4, 131), (4, 132), (4, 133), (4, 141);

-- ========================================
-- 18. 添加外键约束
-- ========================================
ALTER TABLE `pay_admin_role` 
ADD CONSTRAINT `fk_admin_role_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_admin_role_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_role_right` 
ADD CONSTRAINT `fk_role_right_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_role_right_right` FOREIGN KEY (`right_id`) REFERENCES `pay_right`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_admin_token` 
ADD CONSTRAINT `fk_admin_token_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_permission_middleware` 
ADD CONSTRAINT `fk_middleware_permission` FOREIGN KEY (`permission_id`) REFERENCES `pay_right`(`id`) ON DELETE SET NULL;

ALTER TABLE `pay_permission_cache` 
ADD CONSTRAINT `fk_cache_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_operation_log` 
ADD CONSTRAINT `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE SET NULL;

-- ========================================
-- 19. 创建复合索引优化查询性能
-- ========================================
CREATE INDEX `idx_admin_role_status` ON `pay_admin` (`role_id`, `status`);
CREATE INDEX `idx_right_pid_menu_sort` ON `pay_right` (`pid`, `is_menu`, `sort`);
CREATE INDEX `idx_log_admin_created` ON `pay_operation_log` (`admin_id`, `created_at`);
CREATE INDEX `idx_token_admin_active` ON `pay_admin_token` (`admin_id`, `is_active`, `expires_at`);
CREATE INDEX `idx_middleware_pattern_method` ON `pay_permission_middleware` (`path_pattern`, `method`);

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 完成提示
-- ========================================
SELECT 'Database setup completed successfully!' as message;
