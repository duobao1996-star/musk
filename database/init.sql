-- Musk管理系统数据库初始化脚本
-- 包含完整的表结构、权限菜单、角色配置、管理员账户

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 清空所有表并重置自增ID
TRUNCATE TABLE `pay_role_right`;
TRUNCATE TABLE `pay_operation_log`;
TRUNCATE TABLE `pay_performance_metrics`;
TRUNCATE TABLE `pay_right`;
TRUNCATE TABLE `pay_role`;
TRUNCATE TABLE `pay_admin`;
TRUNCATE TABLE `pay_system_config`;

-- 重置自增ID
ALTER TABLE `pay_role_right` AUTO_INCREMENT = 1;
ALTER TABLE `pay_operation_log` AUTO_INCREMENT = 1;
ALTER TABLE `pay_performance_metrics` AUTO_INCREMENT = 1;
ALTER TABLE `pay_right` AUTO_INCREMENT = 1;
ALTER TABLE `pay_role` AUTO_INCREMENT = 1;
ALTER TABLE `pay_admin` AUTO_INCREMENT = 1;
ALTER TABLE `pay_system_config` AUTO_INCREMENT = 1;

-- 1. 插入角色数据
INSERT INTO `pay_role` (`role_name`, `order_no`, `description`, `is_del`) VALUES
('Super Admin', 1, '系统超级管理员，拥有所有权限', 1),
('Admin', 2, '普通管理员，拥有大部分管理权限', 1),
('Operator', 3, '操作员，拥有基础操作权限', 1),
('Viewer', 4, '只读用户，只能查看数据', 1);

-- 2. 插入菜单权限（menu=1）
INSERT INTO `pay_right` (`right_name`, `description`, `menu`, `sort`, `icon`, `path`, `method`, `is_del`) VALUES
('system_management', '系统管理', 1, 10, 'ri:settings-line', '/system', 'GET', 1),
('permission_management', '权限管理', 1, 11, 'ri:shield-check-line', '/system/permissions', 'GET', 1),
('role_management', '角色管理', 1, 12, 'ri:user-settings-line', '/system/roles', 'GET', 1),
('operation_logs', '操作日志', 1, 13, 'ri:file-list-line', '/system/logs', 'GET', 1),
('performance_monitor', '性能监控', 1, 14, 'ri:dashboard-line', '/system/performance', 'GET', 1);

-- 3. 插入接口权限（menu=0）
INSERT INTO `pay_right` (`right_name`, `description`, `menu`, `sort`, `icon`, `path`, `method`, `is_del`) VALUES
-- 认证相关
('api_login', '用户登录', 0, 1, NULL, '/api/login', 'POST', 1),
('api_logout', '用户登出', 0, 2, NULL, '/api/logout', 'POST', 1),
('api_refresh_token', '刷新令牌', 0, 3, NULL, '/api/refresh-token', 'POST', 1),
('api_get_profile', '获取个人信息', 0, 4, NULL, '/api/me', 'GET', 1),

-- 权限管理接口
('api_permissions_list', '查看权限列表', 0, 10, NULL, '/api/permissions', 'GET', 1),
('api_permissions_create', '创建权限', 0, 11, NULL, '/api/permissions', 'POST', 1),
('api_permissions_update', '更新权限', 0, 12, NULL, '/api/permissions/{id}', 'PUT', 1),
('api_permissions_delete', '删除权限', 0, 13, NULL, '/api/permissions/{id}', 'DELETE', 1),
('api_permissions_detail', '查看权限详情', 0, 14, NULL, '/api/permissions/{id}', 'GET', 1),
('api_permissions_tree', '查看权限树', 0, 15, NULL, '/api/permissions/tree', 'GET', 1),
('api_permissions_menu', '查看菜单权限', 0, 16, NULL, '/api/permissions/menu', 'GET', 1),

-- 角色管理接口
('api_roles_list', '查看角色列表', 0, 20, NULL, '/api/roles', 'GET', 1),
('api_roles_create', '创建角色', 0, 21, NULL, '/api/roles', 'POST', 1),
('api_roles_update', '更新角色', 0, 22, NULL, '/api/roles/{id}', 'PUT', 1),
('api_roles_delete', '删除角色', 0, 23, NULL, '/api/roles/{id}', 'DELETE', 1),
('api_roles_detail', '查看角色详情', 0, 24, NULL, '/api/roles/{id}', 'GET', 1),
('api_roles_rights', '查看角色权限', 0, 25, NULL, '/api/roles/{id}/rights', 'GET', 1),
('api_roles_set_rights', '设置角色权限', 0, 26, NULL, '/api/roles/{id}/rights', 'POST', 1),
('api_roles_all_rights_tree', '查看全部权限树', 0, 27, NULL, '/api/roles/all-rights-tree', 'GET', 1),

-- 操作日志接口
('api_logs_list', '查看操作日志', 0, 30, NULL, '/api/operation-logs', 'GET', 1),
('api_logs_stats', '查看操作统计', 0, 31, NULL, '/api/operation-logs/stats', 'GET', 1),
('api_logs_login', '查看登录日志', 0, 32, NULL, '/api/operation-logs/login', 'GET', 1),
('api_logs_clean', '清理旧日志', 0, 33, NULL, '/api/operation-logs/clean', 'POST', 1),

-- 软删除日志接口
('api_soft_delete_logs', '查看已删除日志', 0, 34, NULL, '/api/soft-delete/logs', 'GET', 1),
('api_soft_delete_restore', '恢复已删除日志', 0, 35, NULL, '/api/soft-delete/logs/restore', 'POST', 1),
('api_soft_delete_force', '彻底删除日志', 0, 36, NULL, '/api/soft-delete/logs/force', 'DELETE', 1),
('api_soft_delete_cleanup', '回收站清理', 0, 37, NULL, '/api/soft-delete/cleanup', 'POST', 1),

-- 性能监控接口
('api_performance_stats', '性能统计', 0, 40, NULL, '/api/performance/stats', 'GET', 1),
('api_performance_slow_queries', '慢查询列表', 0, 41, NULL, '/api/performance/slow-queries', 'GET', 1),

-- 系统健康检查
('api_health', '健康检查', 0, 50, NULL, '/api/health', 'GET', 1),
('api_ready', '就绪检查', 0, 51, NULL, '/api/ready', 'GET', 1);

-- 4. 分配角色权限
-- 超级管理员拥有所有权限
INSERT INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 1, id FROM `pay_right` WHERE `is_del` = 1;

-- 普通管理员权限（排除敏感操作）
INSERT INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 2, id FROM `pay_right` 
WHERE `is_del` = 1 
AND `right_name` NOT IN (
    'api_logs_clean',
    'api_soft_delete_force',
    'api_soft_delete_cleanup'
);

-- 操作员权限
INSERT INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 3, id FROM `pay_right` 
WHERE `is_del` = 1 
AND `right_name` IN (
    'api_login', 'api_logout', 'api_refresh_token', 'api_get_profile',
    'api_permissions_list', 'api_permissions_detail', 'api_permissions_tree', 'api_permissions_menu',
    'api_roles_list', 'api_roles_detail', 'api_roles_rights',
    'api_logs_list', 'api_performance_stats',
    'system_management', 'permission_management', 'role_management', 'operation_logs', 'performance_monitor'
);

-- 只读用户权限
INSERT INTO `pay_role_right` (`role_id`, `right_id`)
SELECT 4, id FROM `pay_right` 
WHERE `is_del` = 1 
AND `right_name` IN (
    'api_login', 'api_logout', 'api_refresh_token', 'api_get_profile',
    'api_permissions_list', 'api_permissions_detail', 'api_permissions_tree', 'api_permissions_menu',
    'api_roles_list', 'api_roles_detail', 'api_roles_rights', 'api_roles_all_rights_tree',
    'api_logs_list', 'api_logs_stats', 'api_performance_stats', 'api_performance_slow_queries',
    'system_management', 'permission_management', 'role_management', 'operation_logs', 'performance_monitor'
);

-- 5. 创建管理员账户
INSERT INTO `pay_admin` (`user_name`, `user_password`, `role_id`, `status`, `email`) VALUES
('admin', '$2y$10$Nbz0WoIjaC6LpmEdx3TnbuiWTnVlHu7.GHsVfna6PQA7ByuGQQfP6', 1, 1, 'admin@example.com'),
('manager', '$2y$10$Nbz0WoIjaC6LpmEdx3TnbuiWTnVlHu7.GHsVfna6PQA7ByuGQQfP6', 2, 1, 'manager@example.com'),
('operator', '$2y$10$Nbz0WoIjaC6LpmEdx3TnbuiWTnVlHu7.GHsVfna6PQA7ByuGQQfP6', 3, 1, 'operator@example.com'),
('viewer', '$2y$10$Nbz0WoIjaC6LpmEdx3TnbuiWTnVlHu7.GHsVfna6PQA7ByuGQQfP6', 4, 1, 'viewer@example.com');

-- 6. 插入系统配置
INSERT INTO `pay_system_config` (`name`, `value`) VALUES
('system_name', 'Musk管理系统'),
('system_version', '2.0.0'),
('system_logo', '/static/images/logo.png'),
('jwt_secret', 'your-secret-key-change-this-in-production'),
('jwt_expire', '7200'),
('password_min_length', '6'),
('password_require_special', '1'),
('log_retention_days', '30'),
('log_max_size', '100'),
('operation_log_enabled', '1'),
('performance_monitor_enabled', '1'),
('slow_query_threshold', '1000'),
('memory_limit', '512'),
('default_page_size', '15'),
('max_page_size', '100'),
('upload_max_size', '10485760'),
('upload_allowed_types', 'jpg,jpeg,png,gif,pdf,doc,docx'),
('cache_enabled', '1'),
('cache_ttl', '3600'),
('theme_color', '#409EFF'),
('sidebar_collapsed', '0'),
('language', 'zh-CN'),
('notification_enabled', '1'),
('notification_types', 'system,security,performance'),
('backup_enabled', '1'),
('backup_retention_days', '7'),
('auto_backup_enabled', '0');

SET FOREIGN_KEY_CHECKS = 1;

-- 显示初始化结果
SELECT '=== 数据库初始化完成 ===' as info;
SELECT '=== 角色数据 ===' as info;
SELECT id, role_name, description FROM pay_role;

SELECT '=== 权限统计 ===' as info;
SELECT 
    menu,
    CASE WHEN menu = 1 THEN '菜单权限' ELSE '接口权限' END as type,
    COUNT(*) as count
FROM pay_right 
WHERE is_del = 1 
GROUP BY menu;

SELECT '=== 管理员账户 ===' as info;
SELECT a.id, a.user_name, r.role_name, a.email, a.status 
FROM pay_admin a 
LEFT JOIN pay_role r ON a.role_id = r.id;

SELECT '=== 角色权限统计 ===' as info;
SELECT r.role_name, COUNT(rr.right_id) as permission_count
FROM pay_role r
LEFT JOIN pay_role_right rr ON r.id = rr.role_id
WHERE r.is_del = 1
GROUP BY r.id, r.role_name
ORDER BY r.order_no;
