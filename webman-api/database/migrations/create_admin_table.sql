-- 创建管理员表
-- 如果表已存在则跳过

CREATE TABLE IF NOT EXISTS `pay_admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(50) NOT NULL COMMENT '用户名',
  `email` varchar(100) NOT NULL COMMENT '邮箱',
  `user_password` varchar(255) NOT NULL COMMENT '密码',
  `role_id` int(11) DEFAULT 1 COMMENT '角色ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态：1=正常，0=禁用',
  `ctime` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `etime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '修改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_name` (`user_name`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员表';

-- 创建管理员token表
CREATE TABLE IF NOT EXISTS `pay_admin_token` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `token` varchar(500) NOT NULL COMMENT 'JWT令牌',
  `expires_at` timestamp NOT NULL COMMENT '过期时间',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='管理员令牌表';

-- 插入默认管理员账号
INSERT IGNORE INTO `pay_admin` (`id`, `user_name`, `email`, `user_password`, `role_id`, `status`) VALUES
(1, 'admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test', 1, 1);
