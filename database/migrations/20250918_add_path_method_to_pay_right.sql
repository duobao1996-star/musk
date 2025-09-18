-- 添加 path 与 method 字段到 pay_right，并建立唯一索引
ALTER TABLE `pay_right`
  ADD COLUMN `path` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '接口路径(如 /api/permissions)' AFTER `icon`,
  ADD COLUMN `method` VARCHAR(10) NOT NULL DEFAULT 'GET' COMMENT 'HTTP方法' AFTER `path`;

-- 唯一索引，避免重复
CREATE UNIQUE INDEX IF NOT EXISTS `uniq_path_method` ON `pay_right` (`path`, `method`);

