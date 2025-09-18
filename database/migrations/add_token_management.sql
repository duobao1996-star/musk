-- 添加令牌管理字段到 pay_admin 表
-- 用于实现真正的登出功能

-- 添加当前有效令牌字段
ALTER TABLE pay_admin ADD COLUMN current_token VARCHAR(500) NULL COMMENT '当前有效令牌';

-- 添加令牌过期时间字段
ALTER TABLE pay_admin ADD COLUMN token_expires_at TIMESTAMP NULL COMMENT '令牌过期时间';

-- 添加令牌创建时间字段
ALTER TABLE pay_admin ADD COLUMN token_created_at TIMESTAMP NULL COMMENT '令牌创建时间';

-- 添加索引以提高查询性能
CREATE INDEX idx_pay_admin_token ON pay_admin(current_token);
CREATE INDEX idx_pay_admin_token_expires ON pay_admin(token_expires_at);

-- 添加注释
ALTER TABLE pay_admin COMMENT = '管理员表 - 支持令牌管理';
