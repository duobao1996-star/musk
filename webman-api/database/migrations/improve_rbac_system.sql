-- 改进RBAC权限系统数据库迁移脚本
-- 执行前请备份现有数据

-- 1. 修改权限表结构，添加路径和方法字段
ALTER TABLE `pay_right` 
ADD COLUMN `path` VARCHAR(255) DEFAULT NULL COMMENT 'API路径' AFTER `description`,
ADD COLUMN `method` VARCHAR(10) DEFAULT 'GET' COMMENT 'HTTP方法' AFTER `path`,
ADD COLUMN `is_menu` TINYINT(1) DEFAULT 1 COMMENT '是否菜单项' AFTER `method`,
ADD COLUMN `component` VARCHAR(255) DEFAULT NULL COMMENT '前端组件路径' AFTER `is_menu`,
ADD COLUMN `redirect` VARCHAR(255) DEFAULT NULL COMMENT '重定向路径' AFTER `component`,
ADD COLUMN `hidden` TINYINT(1) DEFAULT 0 COMMENT '是否隐藏' AFTER `redirect`,
ADD COLUMN `always_show` TINYINT(1) DEFAULT 1 COMMENT '是否总是显示' AFTER `hidden`,
ADD COLUMN `no_cache` TINYINT(1) DEFAULT 0 COMMENT '是否缓存' AFTER `always_show`,
ADD COLUMN `affix` TINYINT(1) DEFAULT 0 COMMENT '是否固定标签' AFTER `no_cache`,
ADD COLUMN `breadcrumb` TINYINT(1) DEFAULT 1 COMMENT '是否显示面包屑' AFTER `affix`,
ADD COLUMN `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '激活菜单' AFTER `breadcrumb`;

-- 2. 创建权限中间件表
CREATE TABLE IF NOT EXISTS `pay_permission_middleware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '中间件名称',
  `path_pattern` varchar(255) NOT NULL COMMENT '路径匹配模式',
  `method` varchar(10) DEFAULT 'ALL' COMMENT 'HTTP方法',
  `permission_id` int(11) DEFAULT NULL COMMENT '关联权限ID',
  `is_public` tinyint(1) DEFAULT 0 COMMENT '是否公开接口',
  `description` varchar(255) DEFAULT NULL COMMENT '描述',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_path_pattern` (`path_pattern`),
  KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限中间件配置表';

-- 3. 创建管理员角色关联表（如果不存在）
CREATE TABLE IF NOT EXISTS `pay_admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `role_id` int(11) NOT NULL COMMENT '角色ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_role` (`admin_id`, `role_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员角色关联表';

-- 4. 创建权限缓存表
CREATE TABLE IF NOT EXISTS `pay_permission_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `permissions` text NOT NULL COMMENT '权限JSON',
  `menus` text NOT NULL COMMENT '菜单JSON',
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限缓存表';

-- 5. 插入基础权限数据
INSERT INTO `pay_right` (`pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `menu`, `sort`, `icon`, `component`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`) VALUES
-- 仪表盘
(0, 'dashboard', '仪表盘', '/', 'GET', 1, 1, 1, 'ri:dashboard-line', 'Layout', 0, 1, 0, 1, 1, NULL),
(1, 'dashboard.index', '仪表盘首页', '/', 'GET', 1, 1, 1, 'ri:dashboard-line', 'dashboard/index', 0, 1, 0, 0, 1, NULL),

-- 系统管理分组
(0, 'system', '系统管理', '/system', 'GET', 1, 1, 2, 'ri:settings-line', 'Layout', 0, 1, 0, 0, 1, NULL),
(3, 'system.management', '系统管理', '/system', 'GET', 1, 1, 1, 'ri:settings-line', 'system/index', 0, 1, 0, 0, 1, NULL),
(3, 'system.performance', '性能监控', '/system/performance', 'GET', 1, 1, 2, 'ri:monitor-line', 'system/performance', 0, 1, 0, 0, 1, NULL),

-- 管理员管理分组
(0, 'admin', '管理员管理', '/admin', 'GET', 1, 1, 3, 'ri:user-line', 'Layout', 0, 1, 0, 0, 1, NULL),
(6, 'admin.logs', '操作日志', '/system/logs', 'GET', 1, 1, 1, 'ri:file-list-line', 'system/logs', 0, 1, 0, 0, 1, NULL),
(6, 'admin.permissions', '权限管理', '/system/permissions', 'GET', 1, 1, 2, 'ri:key-line', 'system/permissions', 0, 1, 0, 0, 1, NULL),
(6, 'admin.roles', '角色管理', '/system/roles', 'GET', 1, 1, 3, 'ri:user-settings-line', 'system/roles', 0, 1, 0, 0, 1, NULL),

-- API权限
(0, 'api', 'API权限', NULL, NULL, 0, 0, 0, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.list', '获取权限列表', '/api/permissions', 'GET', 0, 0, 1, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.create', '创建权限', '/api/permissions', 'POST', 0, 0, 2, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.update', '更新权限', '/api/permissions/{id}', 'PUT', 0, 0, 3, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.delete', '删除权限', '/api/permissions/{id}', 'DELETE', 0, 0, 4, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.tree', '获取权限树', '/api/permissions/tree', 'GET', 0, 0, 5, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.permissions.menu', '获取菜单权限', '/api/permissions/menu', 'GET', 0, 0, 6, NULL, NULL, 1, 1, 0, 0, 1, NULL),

(10, 'api.roles.list', '获取角色列表', '/api/roles', 'GET', 0, 0, 7, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.roles.create', '创建角色', '/api/roles', 'POST', 0, 0, 8, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.roles.update', '更新角色', '/api/roles/{id}', 'PUT', 0, 0, 9, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.roles.delete', '删除角色', '/api/roles/{id}', 'DELETE', 0, 0, 10, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.roles.rights', '获取角色权限', '/api/roles/{id}/rights', 'GET', 0, 0, 11, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.roles.set-rights', '设置角色权限', '/api/roles/{id}/rights', 'POST', 0, 0, 12, NULL, NULL, 1, 1, 0, 0, 1, NULL),

(10, 'api.logs.list', '获取操作日志', '/api/operation-logs', 'GET', 0, 0, 13, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.logs.stats', '获取日志统计', '/api/operation-logs/stats', 'GET', 0, 0, 14, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.logs.clean', '清理日志', '/api/operation-logs/clean', 'POST', 0, 0, 15, NULL, NULL, 1, 1, 0, 0, 1, NULL),

(10, 'api.performance.status', '获取性能状态', '/api/performance/status', 'GET', 0, 0, 16, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.performance.slow-queries', '获取慢查询', '/api/performance/slow-queries', 'GET', 0, 0, 17, NULL, NULL, 1, 1, 0, 0, 1, NULL),
(10, 'api.performance.trends', '获取性能趋势', '/api/performance/trends', 'GET', 0, 0, 18, NULL, NULL, 1, 1, 0, 0, 1, NULL);

-- 6. 插入权限中间件配置
INSERT INTO `pay_permission_middleware` (`name`, `path_pattern`, `method`, `permission_id`, `is_public`, `description`) VALUES
('登录接口', '/api/login', 'POST', NULL, 1, '用户登录接口'),
('登出接口', '/api/logout', 'POST', NULL, 1, '用户登出接口'),
('刷新令牌', '/api/refresh-token', 'POST', NULL, 1, '刷新JWT令牌'),
('获取用户信息', '/api/me', 'GET', NULL, 1, '获取当前用户信息'),
('API文档', '/api-docs', 'GET', NULL, 1, 'API文档页面'),
('健康检查', '/api/health', 'GET', NULL, 1, '健康检查接口'),
('权限列表', '/api/permissions', 'GET', 11, 0, '获取权限列表'),
('创建权限', '/api/permissions', 'POST', 12, 0, '创建权限'),
('更新权限', '/api/permissions/*', 'PUT', 13, 0, '更新权限'),
('删除权限', '/api/permissions/*', 'DELETE', 14, 0, '删除权限'),
('权限树', '/api/permissions/tree', 'GET', 15, 0, '获取权限树'),
('菜单权限', '/api/permissions/menu', 'GET', 16, 0, '获取菜单权限'),
('角色列表', '/api/roles', 'GET', 17, 0, '获取角色列表'),
('创建角色', '/api/roles', 'POST', 18, 0, '创建角色'),
('更新角色', '/api/roles/*', 'PUT', 19, 0, '更新角色'),
('删除角色', '/api/roles/*', 'DELETE', 20, 0, '删除角色'),
('角色权限', '/api/roles/*/rights', 'GET', 21, 0, '获取角色权限'),
('设置角色权限', '/api/roles/*/rights', 'POST', 22, 0, '设置角色权限'),
('操作日志', '/api/operation-logs', 'GET', 23, 0, '获取操作日志'),
('日志统计', '/api/operation-logs/stats', 'GET', 24, 0, '获取日志统计'),
('清理日志', '/api/operation-logs/clean', 'POST', 25, 0, '清理日志'),
('性能状态', '/api/performance/status', 'GET', 26, 0, '获取性能状态'),
('慢查询', '/api/performance/slow-queries', 'GET', 27, 0, '获取慢查询'),
('性能趋势', '/api/performance/trends', 'GET', 28, 0, '获取性能趋势');

-- 7. 创建超级管理员角色（如果不存在）
INSERT IGNORE INTO `pay_role` (`id`, `role_name`, `description`, `order_no`, `is_del`) VALUES
(1, '超级管理员', '拥有所有权限的超级管理员角色', 1, 1);

-- 8. 为超级管理员分配所有权限
INSERT IGNORE INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, id FROM `pay_right` WHERE `is_del` = 1;

-- 9. 为现有管理员分配超级管理员角色（如果admin_id=1存在）
INSERT IGNORE INTO `pay_admin_role` (`admin_id`, `role_id`) VALUES (1, 1);

-- 10. 创建索引优化查询性能
CREATE INDEX `idx_right_path_method` ON `pay_right` (`path`, `method`);
CREATE INDEX `idx_right_pid_sort` ON `pay_right` (`pid`, `sort`);
CREATE INDEX `idx_role_right_role_id` ON `pay_role_right` (`role_id`);
CREATE INDEX `idx_role_right_right_id` ON `pay_role_right` (`right_id`);
CREATE INDEX `idx_admin_role_admin_id` ON `pay_admin_role` (`admin_id`);
CREATE INDEX `idx_admin_role_role_id` ON `pay_admin_role` (`role_id`);
