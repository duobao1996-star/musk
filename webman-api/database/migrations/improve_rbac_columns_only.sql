-- 仅增补 pay_right 所需字段；每列单独ALTER，配合 --force 可忽略已存在错误
ALTER TABLE `pay_right` ADD COLUMN `path` VARCHAR(255) DEFAULT NULL COMMENT 'API路径' AFTER `description`;
ALTER TABLE `pay_right` ADD COLUMN `method` VARCHAR(10) DEFAULT 'GET' COMMENT 'HTTP方法' AFTER `path`;
ALTER TABLE `pay_right` ADD COLUMN `is_menu` TINYINT(1) DEFAULT 1 COMMENT '是否菜单项' AFTER `method`;
ALTER TABLE `pay_right` ADD COLUMN `component` VARCHAR(255) DEFAULT NULL COMMENT '前端组件路径' AFTER `is_menu`;
ALTER TABLE `pay_right` ADD COLUMN `redirect` VARCHAR(255) DEFAULT NULL COMMENT '重定向路径' AFTER `component`;
ALTER TABLE `pay_right` ADD COLUMN `hidden` TINYINT(1) DEFAULT 0 COMMENT '是否隐藏' AFTER `redirect`;
ALTER TABLE `pay_right` ADD COLUMN `always_show` TINYINT(1) DEFAULT 1 COMMENT '是否总是显示' AFTER `hidden`;
ALTER TABLE `pay_right` ADD COLUMN `no_cache` TINYINT(1) DEFAULT 0 COMMENT '是否缓存' AFTER `always_show`;
ALTER TABLE `pay_right` ADD COLUMN `affix` TINYINT(1) DEFAULT 0 COMMENT '是否固定标签' AFTER `no_cache`;
ALTER TABLE `pay_right` ADD COLUMN `breadcrumb` TINYINT(1) DEFAULT 1 COMMENT '是否显示面包屑' AFTER `affix`;
ALTER TABLE `pay_right` ADD COLUMN `active_menu` VARCHAR(255) DEFAULT NULL COMMENT '激活菜单' AFTER `breadcrumb`;

