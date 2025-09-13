#!/bin/bash

# Webman API 2.0 自动部署脚本
# 支持GitHub Webhook自动部署到宝塔服务器

set -e

# 配置变量
GITHUB_REPO="duobao1996-star/musk"
BAOTA_SERVER="your_baota_server_ip"
BAOTA_USER="root"
BAOTA_PATH="/www/wwwroot/musk"
BAOTA_SSH_KEY="/root/.ssh/baota_key"
WEBHOOK_SECRET="your_webhook_secret"

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# 日志函数
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" >&2
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARN:${NC} $1"
}

info() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] INFO:${NC} $1"
}

# 检查更新函数
check_updates() {
    log "检查GitHub仓库更新..."
    
    # 获取最新提交信息
    LATEST_COMMIT=$(curl -s "https://api.github.com/repos/${GITHUB_REPO}/commits/main" | jq -r '.sha')
    
    # 检查本地记录的提交
    LOCAL_COMMIT_FILE="/app/.last_commit"
    if [ -f "$LOCAL_COMMIT_FILE" ]; then
        LOCAL_COMMIT=$(cat "$LOCAL_COMMIT_FILE")
    else
        LOCAL_COMMIT=""
    fi
    
    if [ "$LATEST_COMMIT" != "$LOCAL_COMMIT" ]; then
        log "发现新提交: $LATEST_COMMIT"
        return 0
    else
        info "没有发现新提交"
        return 1
    fi
}

# 部署到宝塔函数
deploy_to_baota() {
    log "开始部署到宝塔服务器..."
    
    # 检查SSH密钥
    if [ ! -f "$BAOTA_SSH_KEY" ]; then
        error "SSH密钥文件不存在: $BAOTA_SSH_KEY"
        return 1
    fi
    
    # 创建临时部署目录
    TEMP_DIR="/tmp/webman-deploy-$(date +%s)"
    mkdir -p "$TEMP_DIR"
    
    # 复制项目文件
    rsync -av --exclude='.git' \
        --exclude='runtime/logs' \
        --exclude='vendor' \
        --exclude='.env' \
        --exclude='*.log' \
        --exclude='*.cache' \
        --exclude='.DS_Store' \
        /app/ "$TEMP_DIR/"
    
    # 上传到宝塔服务器
    log "上传文件到宝塔服务器..."
    rsync -avz --progress -e "ssh -i $BAOTA_SSH_KEY" \
        --exclude='.git' \
        --exclude='runtime/logs' \
        --exclude='vendor' \
        --exclude='.env' \
        "$TEMP_DIR/" "${BAOTA_USER}@${BAOTA_SERVER}:${BAOTA_PATH}/"
    
    # 在宝塔服务器上执行部署命令
    log "在宝塔服务器上执行部署..."
    ssh -i "$BAOTA_SSH_KEY" "${BAOTA_USER}@${BAOTA_SERVER}" << EOF
        cd ${BAOTA_PATH}
        
        # 设置权限
        chown -R www:www .
        chmod -R 755 .
        chmod -R 777 runtime/
        
        # 安装/更新依赖
        if [ -f "composer.json" ]; then
            echo "更新Composer依赖..."
            composer install --no-dev --optimize-autoloader
        fi
        
        # 重启Webman服务
        if pgrep -f "webman" > /dev/null; then
            echo "重启Webman服务..."
            php start.php restart
        else
            echo "启动Webman服务..."
            php start.php start -d
        fi
        
        echo "部署完成！"
EOF
    
    # 清理临时目录
    rm -rf "$TEMP_DIR"
    
    # 更新本地提交记录
    echo "$LATEST_COMMIT" > /app/.last_commit
    
    log "部署完成！"
}

# 处理GitHub Webhook
handle_webhook() {
    if [ -z "$1" ]; then
        error "缺少Webhook数据"
        return 1
    fi
    
    # 验证Webhook签名（可选）
    # 这里可以添加GitHub Webhook签名验证逻辑
    
    log "收到GitHub Webhook，开始自动部署..."
    deploy_to_baota
}

# 主循环
main_loop() {
    log "启动自动部署监控..."
    
    while true; do
        if check_updates; then
            deploy_to_baota
        fi
        
        # 等待5分钟再检查
        sleep 300
    done
}

# 启动Webhook服务器（可选）
start_webhook_server() {
    log "启动Webhook服务器..."
    
    # 使用nc监听端口
    while true; do
        echo -e "HTTP/1.1 200 OK\r\n\r\nOK" | nc -l -p 9000
        handle_webhook
    done
}

# 主函数
main() {
    case "${1:-loop}" in
        "loop")
            main_loop
            ;;
        "webhook")
            start_webhook_server
            ;;
        "deploy")
            deploy_to_baota
            ;;
        "check")
            check_updates && echo "有新更新" || echo "没有更新"
            ;;
        *)
            echo "用法: $0 [loop|webhook|deploy|check]"
            echo "  loop    - 循环检查更新并部署（默认）"
            echo "  webhook - 启动Webhook服务器"
            echo "  deploy  - 立即部署"
            echo "  check   - 检查更新"
            exit 1
            ;;
    esac
}

# 执行主函数
main "$@"
