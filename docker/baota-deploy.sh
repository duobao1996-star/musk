#!/bin/bash

# 宝塔自动部署脚本
# 用于在宝塔服务器上自动部署Webman API

set -e

# 配置变量
PROJECT_NAME="webman-musk"
PROJECT_PATH="/www/wwwroot/${PROJECT_NAME}"
GITHUB_REPO="duobao1996-star/musk"
BACKUP_DIR="/www/backup/${PROJECT_NAME}"
LOG_FILE="/var/log/webman-deploy.log"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日志函数
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" | tee -a "$LOG_FILE"
}

# 创建必要目录
create_directories() {
    log "创建必要目录..."
    mkdir -p "$PROJECT_PATH"
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$(dirname "$LOG_FILE")"
}

# 备份当前版本
backup_current() {
    if [ -d "$PROJECT_PATH" ] && [ "$(ls -A "$PROJECT_PATH")" ]; then
        log "备份当前版本..."
        BACKUP_NAME="backup-$(date +%Y%m%d-%H%M%S)"
        tar -czf "${BACKUP_DIR}/${BACKUP_NAME}.tar.gz" -C "$(dirname "$PROJECT_PATH")" "$(basename "$PROJECT_PATH")"
        log "备份完成: ${BACKUP_DIR}/${BACKUP_NAME}.tar.gz"
    fi
}

# 停止服务
stop_service() {
    log "停止Webman服务..."
    cd "$PROJECT_PATH" 2>/dev/null || true
    if pgrep -f "webman" > /dev/null; then
        php start.php stop 2>/dev/null || true
        sleep 2
    fi
}

# 拉取最新代码
pull_code() {
    log "拉取最新代码..."
    cd "$PROJECT_PATH"
    
    if [ -d ".git" ]; then
        git fetch origin
        git reset --hard origin/main
        git clean -fd
    else
        rm -rf * .[^.]* 2>/dev/null || true
        git clone "https://github.com/${GITHUB_REPO}.git" .
    fi
}

# 安装依赖
install_dependencies() {
    log "安装Composer依赖..."
    cd "$PROJECT_PATH"
    
    if [ -f "composer.json" ]; then
        composer install --no-dev --optimize-autoloader --no-interaction
    fi
}

# 设置权限
set_permissions() {
    log "设置文件权限..."
    chown -R www:www "$PROJECT_PATH"
    chmod -R 755 "$PROJECT_PATH"
    chmod -R 777 "$PROJECT_PATH/runtime" 2>/dev/null || true
}

# 配置环境
configure_env() {
    log "配置环境文件..."
    cd "$PROJECT_PATH"
    
    if [ ! -f ".env" ]; then
        cp env.example .env
        log "已创建.env文件，请手动配置数据库信息"
    fi
}

# 启动服务
start_service() {
    log "启动Webman服务..."
    cd "$PROJECT_PATH"
    php start.php start -d
    
    # 等待服务启动
    sleep 3
    
    # 检查服务状态
    if pgrep -f "webman" > /dev/null; then
        log "Webman服务启动成功"
    else
        error "Webman服务启动失败"
        return 1
    fi
}

# 健康检查
health_check() {
    log "执行健康检查..."
    
    # 检查端口
    if netstat -tlnp 2>/dev/null | grep -q ":8787"; then
        log "端口8787正在监听"
    else
        error "端口8787未监听"
        return 1
    fi
    
    # 检查API响应
    if curl -f -s "http://127.0.0.1:8787/api-docs" > /dev/null; then
        log "API文档页面正常"
    else
        warn "API文档页面无法访问"
    fi
}

# 清理旧备份
cleanup_backups() {
    log "清理旧备份..."
    find "$BACKUP_DIR" -name "backup-*.tar.gz" -mtime +7 -delete 2>/dev/null || true
}

# 主部署函数
deploy() {
    log "开始部署 ${PROJECT_NAME}..."
    
    create_directories
    backup_current
    stop_service
    pull_code
    install_dependencies
    set_permissions
    configure_env
    start_service
    
    if health_check; then
        log "部署成功完成！"
        cleanup_backups
    else
        error "部署失败，请检查日志"
        return 1
    fi
}

# 回滚函数
rollback() {
    log "开始回滚..."
    
    LATEST_BACKUP=$(ls -t "${BACKUP_DIR}"/backup-*.tar.gz 2>/dev/null | head -1)
    if [ -z "$LATEST_BACKUP" ]; then
        error "没有找到备份文件"
        return 1
    fi
    
    stop_service
    rm -rf "$PROJECT_PATH"
    tar -xzf "$LATEST_BACKUP" -C "$(dirname "$PROJECT_PATH")"
    set_permissions
    start_service
    
    log "回滚完成: $LATEST_BACKUP"
}

# 主函数
main() {
    case "${1:-deploy}" in
        "deploy")
            deploy
            ;;
        "rollback")
            rollback
            ;;
        "status")
            if pgrep -f "webman" > /dev/null; then
                echo "Webman服务正在运行"
            else
                echo "Webman服务未运行"
            fi
            ;;
        "logs")
            tail -f "$LOG_FILE"
            ;;
        *)
            echo "用法: $0 [deploy|rollback|status|logs]"
            echo "  deploy   - 部署最新版本（默认）"
            echo "  rollback - 回滚到上一个版本"
            echo "  status   - 检查服务状态"
            echo "  logs     - 查看部署日志"
            exit 1
            ;;
    esac
}

# 执行主函数
main "$@"
