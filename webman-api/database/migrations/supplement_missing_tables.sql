-- 补充缺失的表和字段
SET NAMES utf8mb4;

-- 1. 创建管理员令牌表（如果不存在）
CREATE TABLE IF NOT EXISTS `pay_admin_token` (
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

-- 2. 为管理员表添加缺失的字段
ALTER TABLE `pay_admin` 
ADD COLUMN IF NOT EXISTS `last_login_at` timestamp NULL DEFAULT NULL COMMENT '最后登录时间' AFTER `status`,
ADD COLUMN IF NOT EXISTS `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP' AFTER `last_login_at`,
ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0 COMMENT '登录次数' AFTER `last_login_ip`,
ADD COLUMN IF NOT EXISTS `avatar` varchar(255) DEFAULT NULL COMMENT '头像URL' AFTER `phone`;

-- 3. 为角色表添加缺失的字段
ALTER TABLE `pay_role` 
ADD COLUMN IF NOT EXISTS `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用' AFTER `order_no`;

-- 4. 为权限表添加缺失的字段
ALTER TABLE `pay_right` 
ADD COLUMN IF NOT EXISTS `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=启用，0=禁用' AFTER `sort`;

-- 5. 确保操作日志表有所有必要字段
ALTER TABLE `pay_operation_log` 
ADD COLUMN IF NOT EXISTS `execution_time` int(11) DEFAULT NULL COMMENT '执行时间(毫秒)' AFTER `response_data`,
ADD COLUMN IF NOT EXISTS `error_message` text DEFAULT NULL COMMENT '错误信息' AFTER `execution_time`;

-- 6. 检查并插入基础角色数据（如果不存在）
INSERT IGNORE INTO `pay_role` (`id`, `role_name`, `description`, `order_no`, `status`, `is_del`) VALUES
(1, '超级管理员', '拥有所有权限的超级管理员角色', 1, 1, 1),
(2, 'Admin', '普通管理员，拥有管理权限', 2, 1, 1),
(3, 'Operator', '操作员，拥有操作权限', 3, 1, 1),
(4, 'Viewer', '只读用户，仅查看权限', 4, 1, 1);

-- 7. 检查并插入基础权限数据（如果不存在）
-- 仪表盘
INSERT IGNORE INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(1, 0, 'dashboard', '仪表盘', '/', 'GET', 1, 'ri:dashboard-line', 'DashboardView', NULL, 0, 1, 0, 1, 1, NULL, 100, 1, 1);

-- 系统管理分组
INSERT IGNORE INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(10, 0, 'system', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'Layout', '/system/management', 0, 1, 0, 0, 1, NULL, 200, 1, 1),
(11, 10, 'system.management', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'system/SystemView', NULL, 0, 1, 0, 0, 1, NULL, 210, 1, 1),
(12, 10, 'system.performance', '性能监控', '/system/performance', 'GET', 1, 'ri:line-chart-line', 'system/PerformanceView', NULL, 0, 1, 0, 0, 1, NULL, 220, 1, 1);

-- 管理员管理分组
INSERT IGNORE INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(20, 0, 'admin', '管理员管理', '/admin', 'GET', 1, 'ri:admin-line', 'Layout', '/system/logs', 0, 1, 0, 0, 1, NULL, 300, 1, 1),
(21, 20, 'admin.logs', '操作日志', '/system/logs', 'GET', 1, 'ri:file-list-line', 'system/LogView', NULL, 0, 1, 0, 0, 1, NULL, 310, 1, 1),
(22, 20, 'admin.permissions', '权限管理', '/system/permissions', 'GET', 1, 'ri:key-line', 'system/PermissionView', NULL, 0, 1, 0, 0, 1, NULL, 320, 1, 1),
(23, 20, 'admin.roles', '角色管理', '/system/roles', 'GET', 1, 'ri:user-settings-line', 'system/RoleView', NULL, 0, 1, 0, 0, 1, NULL, 330, 1, 1),
(24, 20, 'admin.accounts', '管理员账号', '/system/admins', 'GET', 1, 'ri:user-line', 'system/AdminView', NULL, 0, 1, 0, 0, 1, NULL, 340, 1, 1);

-- 8. 为超级管理员分配所有权限（如果还没有分配）
INSERT IGNORE INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, id FROM `pay_right` WHERE `is_del` = 1 AND id NOT IN (SELECT right_id FROM pay_role_right WHERE role_id = 1);

-- 9. 确保管理员有角色分配（如果还没有分配）
INSERT IGNORE INTO `pay_admin_role` (`admin_id`, `role_id`) VALUES (1, 1);

-- 10. 创建必要的索引
CREATE INDEX IF NOT EXISTS `idx_admin_role_status` ON `pay_admin` (`role_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_right_pid_menu_sort` ON `pay_right` (`pid`, `is_menu`, `sort`);
CREATE INDEX IF NOT EXISTS `idx_log_admin_created` ON `pay_operation_log` (`admin_id`, `created_at`);
CREATE INDEX IF NOT EXISTS `idx_token_admin_active` ON `pay_admin_token` (`admin_id`, `is_active`, `expires_at`);

-- 11. 添加外键约束（如果还没有）
ALTER TABLE `pay_admin_role` 
ADD CONSTRAINT IF NOT EXISTS `fk_admin_role_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT IF NOT EXISTS `fk_admin_role_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_role_right` 
ADD CONSTRAINT IF NOT EXISTS `fk_role_right_role` FOREIGN KEY (`role_id`) REFERENCES `pay_role`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT IF NOT EXISTS `fk_role_right_right` FOREIGN KEY (`right_id`) REFERENCES `pay_right`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_admin_token` 
ADD CONSTRAINT IF NOT EXISTS `fk_admin_token_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE CASCADE;

ALTER TABLE `pay_operation_log` 
ADD CONSTRAINT IF NOT EXISTS `fk_log_admin` FOREIGN KEY (`admin_id`) REFERENCES `pay_admin`(`id`) ON DELETE SET NULL;

SELECT 'Database migration completed successfully!' as message;
