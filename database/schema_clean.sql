-- Clean schema for Webman API 2.0
-- Safe to import on empty DB; on non-empty DB it will DROP and recreate

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables
DROP TABLE IF EXISTS `pay_role_right`;
DROP TABLE IF EXISTS `pay_operation_log`;
DROP TABLE IF EXISTS `pay_performance_metrics`;
DROP TABLE IF EXISTS `pay_right`;
DROP TABLE IF EXISTS `pay_role`;
DROP TABLE IF EXISTS `pay_admin`;
DROP TABLE IF EXISTS `pay_system_config`;

-- pay_admin
CREATE TABLE `pay_admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `role_id` int NOT NULL DEFAULT 1,
  `ctime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `etime` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` tinyint NOT NULL DEFAULT 1,
  `phone` varchar(11) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `admin_code` int NOT NULL DEFAULT 0,
  `sgin` varchar(255) NOT NULL DEFAULT '',
  `login_id` varchar(255) NOT NULL DEFAULT '',
  `Remarks` varchar(255) NOT NULL DEFAULT '',
  `user_password_old` varchar(32) NOT NULL DEFAULT '',
  `current_token` varchar(500) DEFAULT NULL,
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `token_created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_name` (`user_name`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_role
CREATE TABLE `pay_role` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) DEFAULT NULL,
  `order_no` int DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `create_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `modify_time` datetime DEFAULT NULL,
  `is_del` int NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_right
CREATE TABLE `pay_right` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pid` varchar(100) DEFAULT NULL,
  `right_name` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `menu` tinyint DEFAULT NULL,
  `sort` tinyint NOT NULL DEFAULT 0,
  `icon` varchar(255) DEFAULT NULL,
  `path` varchar(255) NOT NULL DEFAULT '',
  `method` varchar(10) NOT NULL DEFAULT 'GET',
  `is_del` tinyint DEFAULT 1,
  `delete_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_isdel_sort` (`is_del`,`sort`),
  UNIQUE KEY `uniq_path_method` (`path`,`method`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_role_right
CREATE TABLE `pay_role_right` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` varchar(100) DEFAULT NULL,
  `right_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_role` (`role_id`),
  KEY `idx_right` (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_operation_log
CREATE TABLE `pay_operation_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `admin_name` varchar(50) DEFAULT NULL,
  `operation_type` varchar(50) NOT NULL,
  `operation_module` varchar(50) DEFAULT NULL,
  `operation_desc` varchar(500) DEFAULT NULL,
  `request_method` varchar(10) DEFAULT NULL,
  `request_url` varchar(500) DEFAULT NULL,
  `request_params` text,
  `response_code` int DEFAULT NULL,
  `response_msg` varchar(500) DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `operation_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` tinyint DEFAULT 1,
  `is_del` tinyint DEFAULT 1,
  `delete_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_isdel_time` (`is_del`,`operation_time`),
  KEY `idx_type` (`operation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_performance_metrics
CREATE TABLE `pay_performance_metrics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(255) NOT NULL,
  `method` varchar(10) NOT NULL,
  `response_time` decimal(10,3) NOT NULL,
  `memory_usage` decimal(10,2) NOT NULL,
  `peak_memory` decimal(10,2) NOT NULL,
  `request_size` int NOT NULL,
  `response_size` int NOT NULL,
  `status_code` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_del` tinyint DEFAULT 1,
  `delete_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_endpoint_time` (`endpoint`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pay_system_config
CREATE TABLE `pay_system_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- Optional seed data
INSERT INTO `pay_role` (`id`,`role_name`,`order_no`,`description`,`is_del`) VALUES (1,'Super Admin',1,'系统超级管理员',1);
INSERT INTO `pay_admin` (`id`,`user_name`,`user_password`,`role_id`,`status`) VALUES (1,'admin', '$2y$10$5iO3j1BvC3pC1j1wF6o1J.xf6a7x5vYl2rHn3Zy3n1Y8kqN7sU3uG', 1, 1);
-- 密码为 Admin@12345（如需修改可导入后更改）

-- 基础权限示例（可用同步脚本补齐其余权限）
-- 完整接口权限（按当前 route.php 生成）
INSERT INTO `pay_right` (`right_name`,`description`,`menu`,`sort`,`path`,`method`,`is_del`) VALUES
('GET /api','API首页',0,0,'/api','GET',1),
('GET /api/health','健康检查',0,0,'/api/health','GET',1),
('GET /api/ready','就绪检查',0,0,'/api/ready','GET',1),
('POST /api/login','登录',0,0,'/api/login','POST',1),
('POST /api/refresh-token','刷新令牌',0,0,'/api/refresh-token','POST',1),
('POST /api/logout','登出',0,0,'/api/logout','POST',1),
('GET /api/me','获取个人信息',0,0,'/api/me','GET',1),

('GET /api/roles','查看角色列表',0,0,'/api/roles','GET',1),
('POST /api/roles','创建角色',0,0,'/api/roles','POST',1),
('GET /api/roles/all-rights-tree','查看全部权限树',0,0,'/api/roles/all-rights-tree','GET',1),
('GET /api/roles/{id}','查看角色详情',0,0,'/api/roles/{id}','GET',1),
('PUT /api/roles/{id}','更新角色',0,0,'/api/roles/{id}','PUT',1),
('DELETE /api/roles/{id}','删除角色',0,0,'/api/roles/{id}','DELETE',1),
('GET /api/roles/{id}/rights','查看角色权限',0,0,'/api/roles/{id}/rights','GET',1),
('POST /api/roles/{id}/rights','设置角色权限',0,0,'/api/roles/{id}/rights','POST',1),

('GET /api/permissions','查看权限列表',0,0,'/api/permissions','GET',1),
('POST /api/permissions','创建权限',0,0,'/api/permissions','POST',1),
('GET /api/permissions/tree','查看权限树',0,0,'/api/permissions/tree','GET',1),
('GET /api/permissions/menu','查看菜单权限',0,0,'/api/permissions/menu','GET',1),
('GET /api/permissions/{id}','查看权限详情',0,0,'/api/permissions/{id}','GET',1),
('PUT /api/permissions/{id}','更新权限',0,0,'/api/permissions/{id}','PUT',1),
('DELETE /api/permissions/{id}','删除权限',0,0,'/api/permissions/{id}','DELETE',1),

('GET /api/operation-logs','查看操作日志',0,0,'/api/operation-logs','GET',1),
('GET /api/operation-logs/stats','查看操作统计',0,0,'/api/operation-logs/stats','GET',1),
('GET /api/operation-logs/login','查看登录日志',0,0,'/api/operation-logs/login','GET',1),
('POST /api/operation-logs/clean','清理旧日志',0,0,'/api/operation-logs/clean','POST',1),

('GET /api/soft-delete/logs','查看已删除日志',0,0,'/api/soft-delete/logs','GET',1),
('POST /api/soft-delete/logs/restore','恢复已删除日志',0,0,'/api/soft-delete/logs/restore','POST',1),
('DELETE /api/soft-delete/force-delete/log','彻底删除日志',0,0,'/api/soft-delete/force-delete/log','DELETE',1),
('POST /api/soft-delete/cleanup','回收站清理',0,0,'/api/soft-delete/cleanup','POST',1),

('GET /api/performance/stats','性能统计',0,0,'/api/performance/stats','GET',1),
('GET /api/performance/slow-queries','慢查询列表',0,0,'/api/performance/slow-queries','GET',1);


