-- RBAC 全量重置（会删除并重建相关表）
SET NAMES utf8mb4;

-- 1) 删除旧表（如存在）
DROP TABLE IF EXISTS `pay_permission_cache`;
DROP TABLE IF EXISTS `pay_permission_middleware`;
DROP TABLE IF EXISTS `pay_admin_role`;
DROP TABLE IF EXISTS `pay_role_right`;
DROP TABLE IF EXISTS `pay_role`;
DROP TABLE IF EXISTS `pay_right`;

-- 2) 建表：权限表
CREATE TABLE `pay_right` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT 0 COMMENT '父ID，根为0',
  `right_name` varchar(120) NOT NULL COMMENT '唯一编码',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '名称/描述(中文显示)',
  `path` varchar(255) DEFAULT NULL COMMENT '路由或接口路径',
  `method` varchar(10) DEFAULT 'GET' COMMENT 'HTTP方法，用于接口权限',
  `is_menu` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否菜单',
  `icon` varchar(120) DEFAULT NULL COMMENT '图标',
  `component` varchar(255) DEFAULT NULL COMMENT '前端组件路径',
  `redirect` varchar(255) DEFAULT NULL,
  `hidden` tinyint(1) NOT NULL DEFAULT 0,
  `always_show` tinyint(1) NOT NULL DEFAULT 1,
  `no_cache` tinyint(1) NOT NULL DEFAULT 0,
  `affix` tinyint(1) NOT NULL DEFAULT 0,
  `breadcrumb` tinyint(1) NOT NULL DEFAULT 1,
  `active_menu` varchar(255) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `is_del` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_right_name` (`right_name`),
  KEY `idx_pid_sort` (`pid`,`sort`),
  KEY `idx_path_method` (`path`,`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3) 角色表
CREATE TABLE `pay_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT '',
  `order_no` int(11) NOT NULL DEFAULT 0,
  `is_del` tinyint(1) NOT NULL DEFAULT 1,
  `create_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) 角色-权限表
CREATE TABLE `pay_role_right` (
  `role_id` int(11) NOT NULL,
  `right_id` int(11) NOT NULL,
  UNIQUE KEY `uk_role_right` (`role_id`,`right_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_right_id` (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) 管理员-角色表
CREATE TABLE `pay_admin_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_role` (`admin_id`,`role_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) 权限中间件表
CREATE TABLE `pay_permission_middleware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `path_pattern` varchar(255) NOT NULL,
  `method` varchar(10) DEFAULT 'ALL',
  `permission_id` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_path_pattern` (`path_pattern`),
  KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7) 权限缓存表（可选）
CREATE TABLE `pay_permission_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `permissions` mediumtext NOT NULL,
  `menus` mediumtext NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8) 菜单（顶级/分组/子项）与 API 权限初始化
-- Dashboard
INSERT INTO `pay_right` (`id`,`pid`,`right_name`,`description`,`path`,`method`,`is_menu`,`icon`,`component`,`sort`) VALUES
(1,0,'dashboard','仪表盘','/','GET',1,'ri:dashboard-line','dashboard/index',1);

-- 系统管理分组
INSERT INTO `pay_right` (`id`,`pid`,`right_name`,`description`,`path`,`method`,`is_menu`,`icon`,`component`,`sort`) VALUES
(10,0,'system','系统管理','/system','GET',1,'ri:settings-line','Layout',2),
(11,10,'system.management','系统管理','/system','GET',1,'ri:settings-line','system/index',1),
(12,10,'system.performance','性能监控','/system/performance','GET',1,'ri:monitor-line','system/performance',2);

-- 管理员管理分组
INSERT INTO `pay_right` (`id`,`pid`,`right_name`,`description`,`path`,`method`,`is_menu`,`icon`,`component`,`sort`) VALUES
(20,0,'admin','管理员管理','/admin','GET',1,'ri:user-line','Layout',3),
(21,20,'admin.logs','操作日志','/system/logs','GET',1,'ri:file-list-line','system/logs',1),
(22,20,'admin.permissions','权限管理','/system/permissions','GET',1,'ri:key-line','system/permissions',2),
(23,20,'admin.roles','角色管理','/system/roles','GET',1,'ri:user-settings-line','system/roles',3);

-- API 权限（非菜单）
INSERT INTO `pay_right` (`id`,`pid`,`right_name`,`description`,`path`,`method`,`is_menu`,`sort`) VALUES
(100,0,'api','API权限',NULL,'GET',0,0),
(101,100,'api.permissions.list','获取权限列表','/api/permissions','GET',0,1),
(102,100,'api.permissions.create','创建权限','/api/permissions','POST',0,2),
(103,100,'api.permissions.update','更新权限','/api/permissions/{id}','PUT',0,3),
(104,100,'api.permissions.delete','删除权限','/api/permissions/{id}','DELETE',0,4),
(105,100,'api.permissions.tree','获取权限树','/api/permissions/tree','GET',0,5),
(106,100,'api.permissions.menu','获取菜单权限','/api/permissions/menu','GET',0,6),
(111,100,'api.roles.list','获取角色列表','/api/roles','GET',0,7),
(112,100,'api.roles.create','创建角色','/api/roles','POST',0,8),
(113,100,'api.roles.update','更新角色','/api/roles/{id}','PUT',0,9),
(114,100,'api.roles.delete','删除角色','/api/roles/{id}','DELETE',0,10),
(115,100,'api.roles.rights','获取角色权限','/api/roles/{id}/rights','GET',0,11),
(116,100,'api.roles.set-rights','设置角色权限','/api/roles/{id}/rights','POST',0,12),
(121,100,'api.logs.list','获取操作日志','/api/operation-logs','GET',0,13),
(122,100,'api.logs.stats','获取日志统计','/api/operation-logs/stats','GET',0,14),
(123,100,'api.logs.clean','清理日志','/api/operation-logs/clean','POST',0,15),
(131,100,'api.performance.status','获取性能状态','/api/performance/stats','GET',0,16),
(132,100,'api.performance.slow-queries','获取慢查询','/api/performance/slow-queries','GET',0,17);

-- 9) 超级管理员赋权
INSERT INTO `pay_role` (`id`,`role_name`,`description`,`order_no`,`is_del`) VALUES
(1,'超级管理员','拥有所有权限',1,1),
(2,'Admin','普通管理员',2,1),
(3,'Operator','操作员',3,1),
(4,'Viewer','只读用户',4,1);

INSERT INTO `pay_role_right` (`role_id`,`right_id`)
SELECT 1, id FROM `pay_right` WHERE is_del=1;

-- 可选：将管理员1设为超管角色
INSERT INTO `pay_admin_role` (`admin_id`,`role_id`) VALUES (1,1);

-- 10) 中间件配置（公开接口）
INSERT INTO `pay_permission_middleware` (`name`,`path_pattern`,`method`,`permission_id`,`is_public`,`description`) VALUES
('登录接口','/api/login','POST',NULL,1,'用户登录'),
('登出接口','/api/logout','POST',NULL,1,'用户登出'),
('刷新令牌','/api/refresh-token','POST',NULL,1,'刷新JWT'),
('用户信息','/api/me','GET',NULL,1,'当前用户信息'),
('健康检查','/api/health','GET',NULL,1,'健康'),
('接口文档','/api-docs','GET',NULL,1,'文档');


