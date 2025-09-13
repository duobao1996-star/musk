-- Webman API 2.0 数据库初始化脚本

-- 创建数据库（如果不存在）
CREATE DATABASE IF NOT EXISTS `newsf1` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `newsf1`;

-- 管理员表
CREATE TABLE IF NOT EXISTS `pay_admin` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL COMMENT '用户名',
    `password` varchar(255) NOT NULL COMMENT '密码',
    `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
    `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
    `role_id` int(11) DEFAULT NULL COMMENT '角色ID',
    `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-正常，0-禁用',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_status` (`status`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 用户表
CREATE TABLE IF NOT EXISTS `pay_user` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL COMMENT '用户名',
    `password` varchar(255) NOT NULL COMMENT '密码',
    `email` varchar(100) DEFAULT NULL COMMENT '邮箱',
    `phone` varchar(20) DEFAULT NULL COMMENT '手机号',
    `user_type` enum('merchant','agent') DEFAULT 'merchant' COMMENT '用户类型',
    `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-正常，0-禁用',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `idx_user_type` (`user_type`),
    KEY `idx_status` (`status`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 角色表
CREATE TABLE IF NOT EXISTS `pay_role` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `role_name` varchar(50) NOT NULL COMMENT '角色名称',
    `description` text COMMENT '角色描述',
    `order_no` int(11) DEFAULT 0 COMMENT '排序号',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_name` (`role_name`),
    KEY `idx_order_no` (`order_no`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色表';

-- 权限表
CREATE TABLE IF NOT EXISTS `pay_right` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `pid` int(11) DEFAULT 0 COMMENT '父级权限ID',
    `right_name` varchar(100) NOT NULL COMMENT '权限名称',
    `description` text COMMENT '权限描述',
    `menu` tinyint(1) DEFAULT 0 COMMENT '是否菜单：1-是，0-否',
    `sort` int(11) DEFAULT 0 COMMENT '排序号',
    `icon` varchar(50) DEFAULT NULL COMMENT '图标',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_pid` (`pid`),
    KEY `idx_menu` (`menu`),
    KEY `idx_sort` (`sort`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='权限表';

-- 角色权限关联表
CREATE TABLE IF NOT EXISTS `pay_role_right` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `role_id` int(11) NOT NULL COMMENT '角色ID',
    `right_id` int(11) NOT NULL COMMENT '权限ID',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `role_right` (`role_id`,`right_id`),
    KEY `idx_role_id` (`role_id`),
    KEY `idx_right_id` (`right_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='角色权限关联表';

-- 操作日志表
CREATE TABLE IF NOT EXISTS `pay_operation_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `admin_id` int(11) DEFAULT NULL COMMENT '管理员ID',
    `admin_name` varchar(50) DEFAULT NULL COMMENT '管理员名称',
    `operation_type` varchar(50) NOT NULL COMMENT '操作类型',
    `operation_module` varchar(50) DEFAULT NULL COMMENT '操作模块',
    `operation_desc` text COMMENT '操作描述',
    `request_url` varchar(255) DEFAULT NULL COMMENT '请求URL',
    `request_method` varchar(10) DEFAULT NULL COMMENT '请求方法',
    `request_params` text COMMENT '请求参数',
    `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
    `user_agent` text COMMENT '用户代理',
    `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-成功，0-失败',
    `operation_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_admin_id` (`admin_id`),
    KEY `idx_operation_type` (`operation_type`),
    KEY `idx_operation_time` (`operation_time`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志表';

-- 性能监控表
CREATE TABLE IF NOT EXISTS `pay_performance_metrics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `endpoint` varchar(255) NOT NULL COMMENT '接口路径',
    `method` varchar(10) NOT NULL COMMENT '请求方法',
    `response_time` decimal(10,3) NOT NULL COMMENT '响应时间(毫秒)',
    `memory_usage` int(11) DEFAULT NULL COMMENT '内存使用(字节)',
    `status_code` int(11) DEFAULT NULL COMMENT '状态码',
    `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
    `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    `is_del` tinyint(1) DEFAULT 0 COMMENT '软删除：0-正常，1-删除',
    `delete_time` timestamp NULL DEFAULT NULL COMMENT '删除时间',
    PRIMARY KEY (`id`),
    KEY `idx_endpoint` (`endpoint`),
    KEY `idx_method` (`method`),
    KEY `idx_response_time` (`response_time`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_is_del` (`is_del`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='性能监控表';

-- 插入初始数据
INSERT IGNORE INTO `pay_role` (`id`, `role_name`, `description`, `order_no`) VALUES
(1, '超级管理员', '拥有所有权限', 1),
(2, '普通管理员', '基础管理权限', 2);

INSERT IGNORE INTO `pay_admin` (`id`, `username`, `password`, `email`, `role_id`) VALUES
(1, 'admin', '$argon2id$v=19$m=65536,t=4,p=3$example', 'admin@example.com', 1);

INSERT IGNORE INTO `pay_right` (`id`, `pid`, `right_name`, `description`, `menu`, `sort`) VALUES
(1, 0, '系统管理', '系统管理模块', 1, 1),
(2, 1, '用户管理', '用户增删改查', 1, 1),
(3, 1, '角色管理', '角色增删改查', 1, 2),
(4, 1, '权限管理', '权限增删改查', 1, 3),
(5, 1, '操作日志', '操作日志查看', 1, 4),
(6, 1, '性能监控', '性能监控查看', 1, 5);
