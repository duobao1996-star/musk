#!/bin/bash

################################################################################
# 项目停止脚本
# 功能：停止所有服务（前端、后端）
################################################################################

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# 项目配置
PROJECT_NAME="Musk管理系统"
BACKEND_DIR="webman-api"
FRONTEND_DIR="musk-admin"
BACKEND_PORT=8787
FRONTEND_PORT=3001

# 获取脚本所在目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

################################################################################
# 工具函数
################################################################################

print_title() {
    echo -e "${CYAN}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  $1"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_step() {
    echo -e "${BLUE}▶ $1${NC}"
}

kill_port() {
    local port=$1
    local service_name=$2
    
    print_step "停止 $service_name (端口 $port)..."
    
    local pids=$(lsof -ti:$port 2>/dev/null)
    if [ -n "$pids" ]; then
        echo "$pids" | xargs kill -9 2>/dev/null || true
        sleep 1
        
        # 验证是否停止
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
            print_warning "$service_name 停止失败"
        else
            print_success "$service_name 已停止"
        fi
    else
        print_info "$service_name 未运行"
    fi
}

################################################################################
# 停止服务
################################################################################

stop_services() {
    print_title "停止所有服务"
    
    # 停止后端服务
    print_step "停止后端服务..."
    if [ -d "$BACKEND_DIR" ]; then
        cd "$BACKEND_DIR"
        php start.php stop 2>/dev/null || true
        sleep 1
        cd ..
    fi
    
    # 强制停止后端端口
    kill_port $BACKEND_PORT "Webman服务"
    
    # 停止前端服务
    if [ -f "logs/frontend.pid" ]; then
        print_step "停止前端服务..."
        FRONTEND_PID=$(cat logs/frontend.pid)
        if ps -p $FRONTEND_PID > /dev/null 2>&1; then
            kill $FRONTEND_PID 2>/dev/null || true
            sleep 1
            print_success "前端服务已停止"
        else
            print_info "前端服务未运行"
        fi
        rm -f logs/frontend.pid
    fi
    
    # 强制停止前端端口
    kill_port $FRONTEND_PORT "Vite服务"
    
    # 清理相关进程
    print_step "清理相关进程..."
    pkill -f "npm run dev" 2>/dev/null || true
    pkill -f "vite" 2>/dev/null || true
    pkill -f "webman" 2>/dev/null || true
    
    echo ""
    print_success "所有服务已停止"
}

################################################################################
# 主函数
################################################################################

main() {
    clear
    
    print_title "$PROJECT_NAME - 停止脚本"
    
    stop_services
    
    echo ""
    print_info "服务已全部停止，可以安全退出"
    echo ""
}

main "$@"

