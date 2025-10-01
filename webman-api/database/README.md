# 数据库管理说明

## 📁 目录结构

```
database/
├── README.md                   # 本说明文档
├── COMPLETE_DATABASE_DESIGN.md # 完整数据库设计文档
├── export_database.sh          # 数据库导出脚本
├── backup/                     # 数据库备份目录
│   ├── database_latest.sql     # 最新备份（软链接）
│   └── database_backup_*.sql.gz # 带时间戳的备份文件
└── migrations/                 # 数据库迁移脚本
    ├── basic_migration.sql     # 基础迁移（推荐使用）
    ├── complete_database_setup.sql # 完整数据库设置
    └── ...                     # 其他迁移脚本
```

## 🚀 快速开始

### 1. 导入数据库（新环境部署）

```bash
# 方式1：使用基础迁移脚本（推荐）
cd webman-api
mysql -u newsf1 -pnewsf1 newsf1 < database/migrations/basic_migration.sql

# 方式2：使用备份文件
cd webman-api
gunzip -c database/backup/database_backup_*.sql.gz | mysql -u newsf1 -pnewsf1 newsf1
```

### 2. 导出数据库（创建备份）

```bash
cd webman-api
bash database/export_database.sh
```

导出后会在 `database/backup/` 目录生成：
- `database_backup_YYYYMMDD_HHMMSS.sql.gz` - 带时间戳的压缩备份
- `database_latest.sql` - 指向最新备份的软链接

## 📊 数据库配置

### 数据库连接信息
```php
// config/think-orm.php
'hostname' => '127.0.0.1',
'database' => 'newsf1',
'username' => 'newsf1',
'password' => 'newsf1',
'hostport' => '3306',
'charset' => 'utf8mb4',
'collation' => 'utf8mb4_unicode_ci',
```

### 创建数据库
```sql
CREATE DATABASE IF NOT EXISTS newsf1 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'newsf1'@'localhost' 
  IDENTIFIED BY 'newsf1';
  
GRANT ALL PRIVILEGES ON newsf1.* TO 'newsf1'@'localhost';
FLUSH PRIVILEGES;
```

## 📋 数据库表结构

### 核心表（12张）

1. **pay_admin** - 管理员表（6条记录）
   - 用户信息、角色关联、状态管理
   
2. **pay_role** - 角色表（4条记录）
   - 超级管理员、Admin、Operator、Viewer

3. **pay_right** - 权限表（45条记录）
   - 三级菜单结构、API权限

4. **pay_admin_role** - 管理员角色关联表
   - 多对多关系

5. **pay_role_right** - 角色权限关联表
   - 多对多关系

6. **pay_admin_token** - 管理员令牌表
   - JWT令牌管理

7. **pay_operation_log** - 操作日志表
   - 完整的审计追踪

8. **pay_admin_login_log** - 登录日志表
   - 登录记录

9. **pay_performance_metrics** - 性能监控表
   - 系统性能数据

10. **pay_permission_cache** - 权限缓存表
    - 权限缓存

11. **pay_permission_middleware** - 权限中间件表
    - 中间件配置

12. **pay_system_config** - 系统配置表
    - 系统配置项

## 🔧 迁移脚本说明

### basic_migration.sql（推荐）
- 创建基础表结构
- 插入初始数据
- 适用于新环境部署

### complete_database_setup.sql
- 完整的数据库设置
- 包含所有表和初始数据
- 适用于全新安装

### reset_rbac_full.sql
- 完整重置RBAC系统
- 删除并重建所有权限相关表
- **警告**: 会清空现有数据

## 📦 备份策略

### 自动备份
建议配置定时任务（crontab）：

```bash
# 每天凌晨2点备份数据库
0 2 * * * /path/to/webman-api/database/export_database.sh

# 每周日凌晨3点清理旧备份（保留30天）
0 3 * * 0 find /path/to/webman-api/database/backup -name "*.gz" -mtime +30 -delete
```

### 手动备份
在重大操作前建议手动备份：

```bash
bash database/export_database.sh
```

## 🔄 恢复数据库

### 从备份恢复

```bash
# 1. 解压备份文件
gunzip database/backup/database_backup_20240101_120000.sql.gz

# 2. 恢复数据库
mysql -u newsf1 -pnewsf1 newsf1 < database/backup/database_backup_20240101_120000.sql

# 3. 重新压缩（可选）
gzip database/backup/database_backup_20240101_120000.sql
```

### 从最新备份恢复

```bash
# database_latest.sql 是指向最新备份的软链接
gunzip -c database/backup/database_latest.sql.gz | mysql -u newsf1 -pnewsf1 newsf1
```

## 🔐 初始账号

### 管理员账号
- **用户名**: admin
- **密码**: password
- **角色**: 超级管理员
- **权限**: 所有权限

### 测试账号
- **testuser** / password (Viewer角色)
- **testadmin1** / password (Admin角色)

**⚠️ 重要**: 部署到生产环境后，请立即修改默认密码！

## 📝 数据库维护

### 优化表
```sql
OPTIMIZE TABLE pay_operation_log;
OPTIMIZE TABLE pay_admin_login_log;
OPTIMIZE TABLE pay_performance_metrics;
```

### 检查表
```sql
CHECK TABLE pay_admin;
CHECK TABLE pay_role;
CHECK TABLE pay_right;
```

### 修复表
```sql
REPAIR TABLE table_name;
```

### 查看表大小
```sql
SELECT 
  table_name AS 'Table',
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'newsf1'
ORDER BY (data_length + index_length) DESC;
```

## 🔍 常见问题

### Q: 导入时提示字符集错误
**A**: 确保MySQL配置支持utf8mb4：
```sql
SHOW VARIABLES LIKE 'character%';
-- 应该显示 utf8mb4
```

### Q: 导入后无法登录
**A**: 检查默认账号：
```sql
SELECT id, user_name, email, status FROM pay_admin WHERE user_name = 'admin';
```

### Q: 权限不生效
**A**: 检查角色权限关联：
```sql
SELECT r.role_name, COUNT(rr.right_id) as permission_count
FROM pay_role r
LEFT JOIN pay_role_right rr ON r.id = rr.role_id
GROUP BY r.id, r.role_name;
```

### Q: 备份文件太大
**A**: 可以只导出结构或只导出数据：
```bash
# 只导出结构
mysqldump -u newsf1 -pnewsf1 --no-data newsf1 > structure.sql

# 只导出数据
mysqldump -u newsf1 -pnewsf1 --no-create-info newsf1 > data.sql
```

## 📚 更多信息

- 完整数据库设计: [COMPLETE_DATABASE_DESIGN.md](COMPLETE_DATABASE_DESIGN.md)
- RBAC系统设计: [../docs/RBAC_SYSTEM_DESIGN.md](../docs/RBAC_SYSTEM_DESIGN.md)
- 部署指南: [../../DEPLOYMENT_GUIDE.md](../../DEPLOYMENT_GUIDE.md)

---

**最后更新**: 2024年1月  
**维护者**: 项目开发团队  
**版本**: 1.0.0
