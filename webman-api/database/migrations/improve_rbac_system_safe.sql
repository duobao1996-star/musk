-- 安全版RBAC迁移：可重复执行，不报错

-- 1) pay_right 增补字段（仅当不存在时添加）
ALTER TABLE `pay_right`
  ADD COLUMN IF NOT EXISTS `path` VARCHAR(255) DEFAULT NULL COMMENT 'API路径' AFTER `description`,
  ADD COLUMN IF NOT EXISTS `method` VARCHAR(10) DEFAULT 'GET' COMMENT 'HTTP方法' AFTER `path`,
  ADD COLUMN IF NOT EXISTS `is_menu` TINYINT(1) DEFAULT 1 COMMENT '是否菜单项' AFTER `method`,
  ADD COLUMN IF NOT EXISTS `component` VARCHAR(255) DEFAULT NULL COMMENT '前端组件路径' AFTER `is_menu`,
  ADD COLUMN IF NOT EXISTS `redirect` VARCHAR(255) DEFAULT NULL COMMENT '重定向路径' AFTER `component`,
  ADD COLUMN IF NOT EXISTS `hidden` TINYINT(1) DEFAULT 0 COMMENT '是否隐藏' AFTER `redirect`,
  ADD COLUMN IF NOT EXISTS `always_show` TINYINT(1) DEFAULT 1 COMMENT '是否总是显示' AFTER `hidden`,
  ADD COLUMN IF NOT EXISTS `no_cache` TINYINT(1) DEFAULT 0 COMMENT '是否缓存' AFTER `always_show`,
  ADD COLUMN IF NOT EXISTS `affix` TINYINT(1) DEFAULT 0 COMMENT '是否固定标签' AFTER `no_cache`,
  ADD COLUMN IF NOT EXISTS `breadcrumb` TINYINT(1) DEFAULT 1 COMMENT '是否显示面包屑' AFTER `affix`,
  ADD COLUMN IF NOT EXISTS `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '激活菜单' AFTER `breadcrumb`;

-- 2) 需要的表（如果不存在则创建）
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

-- 3) 索引（如果不存在则创建）
CREATE INDEX IF NOT EXISTS `idx_right_path_method` ON `pay_right` (`path`, `method`);
CREATE INDEX IF NOT EXISTS `idx_right_pid_sort` ON `pay_right` (`pid`, `sort`);
CREATE INDEX IF NOT EXISTS `idx_role_right_role_id` ON `pay_role_right` (`role_id`);
CREATE INDEX IF NOT EXISTS `idx_role_right_right_id` ON `pay_role_right` (`right_id`);
CREATE INDEX IF NOT EXISTS `idx_admin_role_admin_id` ON `pay_admin_role` (`admin_id`);
CREATE INDEX IF NOT EXISTS `idx_admin_role_role_id` ON `pay_admin_role` (`role_id`);

-- 4) 超级管理员与权限赋权（幂等）
INSERT IGNORE INTO `pay_role` (`id`, `role_name`, `description`, `order_no`, `is_del`) VALUES
(1, '超级管理员', '拥有所有权限的超级管理员角色', 1, 1);

INSERT IGNORE INTO `pay_admin_role` (`admin_id`, `role_id`) VALUES (1, 1);

-- 将现有 pay_right 全量赋给超管（幂等）
INSERT IGNORE INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, r.id FROM `pay_right` r WHERE r.is_del = 1;


