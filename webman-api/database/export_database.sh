#!/bin/bash

# 数据库导出脚本
# 导出完整的数据库结构和数据

echo "开始导出数据库..."

# 数据库配置
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="newsf1"
DB_USER="newsf1"
DB_PASS="newsf1"

# 导出目录
EXPORT_DIR="$(dirname "$0")/backup"
mkdir -p "$EXPORT_DIR"

# 生成文件名（带时间戳）
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${EXPORT_DIR}/database_backup_${TIMESTAMP}.sql"

# 导出数据库
echo "正在导出数据库到: $BACKUP_FILE"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" \
  --databases "$DB_NAME" \
  --single-transaction \
  --routines \
  --triggers \
  --events \
  --hex-blob \
  --default-character-set=utf8mb4 \
  --add-drop-database \
  --add-drop-table \
  --result-file="$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ 数据库导出成功: $BACKUP_FILE"
    
    # 创建最新版本的软链接
    LATEST_FILE="${EXPORT_DIR}/database_latest.sql"
    ln -sf "$(basename "$BACKUP_FILE")" "$LATEST_FILE"
    echo "✅ 创建最新版本链接: $LATEST_FILE"
    
    # 压缩备份文件
    echo "正在压缩备份文件..."
    gzip "$BACKUP_FILE"
    echo "✅ 压缩完成: ${BACKUP_FILE}.gz"
    
    # 显示文件大小
    BACKUP_SIZE=$(du -h "${BACKUP_FILE}.gz" | cut -f1)
    echo "📦 备份文件大小: $BACKUP_SIZE"
    
else
    echo "❌ 数据库导出失败"
    exit 1
fi

echo "✅ 数据库导出完成！"

