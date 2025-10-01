-- 补充缺失的表和字段 - MySQL兼容版本
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

-- 2. 检查并添加管理员表缺失的字段
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_admin' 
   AND COLUMN_NAME = 'last_login_at') > 0,
  'SELECT "Column last_login_at already exists"',
  'ALTER TABLE pay_admin ADD COLUMN last_login_at timestamp NULL DEFAULT NULL COMMENT "最后登录时间" AFTER status'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_admin' 
   AND COLUMN_NAME = 'last_login_ip') > 0,
  'SELECT "Column last_login_ip already exists"',
  'ALTER TABLE pay_admin ADD COLUMN last_login_ip varchar(45) DEFAULT NULL COMMENT "最后登录IP" AFTER last_login_at'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_admin' 
   AND COLUMN_NAME = 'login_count') > 0,
  'SELECT "Column login_count already exists"',
  'ALTER TABLE pay_admin ADD COLUMN login_count int(11) DEFAULT 0 COMMENT "登录次数" AFTER last_login_ip'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_admin' 
   AND COLUMN_NAME = 'avatar') > 0,
  'SELECT "Column avatar already exists"',
  'ALTER TABLE pay_admin ADD COLUMN avatar varchar(255) DEFAULT NULL COMMENT "头像URL" AFTER phone'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. 检查并添加角色表缺失的字段
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_role' 
   AND COLUMN_NAME = 'status') > 0,
  'SELECT "Column status already exists in pay_role"',
  'ALTER TABLE pay_role ADD COLUMN status tinyint(1) NOT NULL DEFAULT 1 COMMENT "状态：1=启用，0=禁用" AFTER order_no'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. 检查并添加权限表缺失的字段
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_right' 
   AND COLUMN_NAME = 'status') > 0,
  'SELECT "Column status already exists in pay_right"',
  'ALTER TABLE pay_right ADD COLUMN status tinyint(1) NOT NULL DEFAULT 1 COMMENT "状态：1=启用，0=禁用" AFTER sort'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. 检查并添加操作日志表缺失的字段
SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_operation_log' 
   AND COLUMN_NAME = 'execution_time') > 0,
  'SELECT "Column execution_time already exists"',
  'ALTER TABLE pay_operation_log ADD COLUMN execution_time int(11) DEFAULT NULL COMMENT "执行时间(毫秒)" AFTER response_data'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_SCHEMA = DATABASE() 
   AND TABLE_NAME = 'pay_operation_log' 
   AND COLUMN_NAME = 'error_message') > 0,
  'SELECT "Column error_message already exists"',
  'ALTER TABLE pay_operation_log ADD COLUMN error_message text DEFAULT NULL COMMENT "错误信息" AFTER execution_time'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 6. 插入基础角色数据（如果不存在）
INSERT IGNORE INTO `pay_role` (`id`, `role_name`, `description`, `order_no`, `status`, `is_del`) VALUES
(1, '超级管理员', '拥有所有权限的超级管理员角色', 1, 1, 1),
(2, 'Admin', '普通管理员，拥有管理权限', 2, 1, 1),
(3, 'Operator', '操作员，拥有操作权限', 3, 1, 1),
(4, 'Viewer', '只读用户，仅查看权限', 4, 1, 1);

-- 7. 插入基础权限数据（如果不存在）
INSERT IGNORE INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `path`, `method`, `is_menu`, `icon`, `component`, `redirect`, `hidden`, `always_show`, `no_cache`, `affix`, `breadcrumb`, `active_menu`, `sort`, `status`, `is_del`) VALUES
(1, 0, 'dashboard', '仪表盘', '/', 'GET', 1, 'ri:dashboard-line', 'DashboardView', NULL, 0, 1, 0, 1, 1, NULL, 100, 1, 1),
(10, 0, 'system', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'Layout', '/system/management', 0, 1, 0, 0, 1, NULL, 200, 1, 1),
(11, 10, 'system.management', '系统管理', '/system', 'GET', 1, 'ri:settings-line', 'system/SystemView', NULL, 0, 1, 0, 0, 1, NULL, 210, 1, 1),
(12, 10, 'system.performance', '性能监控', '/system/performance', 'GET', 1, 'ri:line-chart-line', 'system/PerformanceView', NULL, 0, 1, 0, 0, 1, NULL, 220, 1, 1),
(20, 0, 'admin', '管理员管理', '/admin', 'GET', 1, 'ri:admin-line', 'Layout', '/system/logs', 0, 1, 0, 0, 1, NULL, 300, 1, 1),
(21, 20, 'admin.logs', '操作日志', '/system/logs', 'GET', 1, 'ri:file-list-line', 'system/LogView', NULL, 0, 1, 0, 0, 1, NULL, 310, 1, 1),
(22, 20, 'admin.permissions', '权限管理', '/system/permissions', 'GET', 1, 'ri:key-line', 'system/PermissionView', NULL, 0, 1, 0, 0, 1, NULL, 320, 1, 1),
(23, 20, 'admin.roles', '角色管理', '/system/roles', 'GET', 1, 'ri:user-settings-line', 'system/RoleView', NULL, 0, 1, 0, 0, 1, NULL, 330, 1, 1),
(24, 20, 'admin.accounts', '管理员账号', '/system/admins', 'GET', 1, 'ri:user-line', 'system/AdminView', NULL, 0, 1, 0, 0, 1, NULL, 340, 1, 1);

-- 8. 为超级管理员分配所有权限
INSERT IGNORE INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, id FROM `pay_right` WHERE `is_del` = 1;

-- 9. 确保管理员有角色分配
INSERT IGNORE INTO `pay_admin_role` (`admin_id`, `role_id`) VALUES (1, 1);

SELECT 'Database migration completed successfully!' as message;
