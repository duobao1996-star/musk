#!/bin/bash

################################################################################
# 项目状态查看脚本
# 功能：查看所有服务的运行状态
################################################################################

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# 项目配置
BACKEND_PORT=8787
FRONTEND_PORT=3001

print_title() {
    echo -e "${CYAN}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  $1"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
}

check_port() {
    local port=$1
    local service=$2
    
    if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
        local pid=$(lsof -ti:$port)
        echo -e "  ${GREEN}●${NC} $service"
        echo -e "    状态: ${GREEN}运行中${NC}"
        echo -e "    端口: $port"
        echo -e "    PID:  $pid"
    else
        echo -e "  ${RED}●${NC} $service"
        echo -e "    状态: ${RED}已停止${NC}"
        echo -e "    端口: $port"
    fi
    echo ""
}

check_database() {
    if mysql -h 127.0.0.1 -u newsf1 -pnewsf1 -e "USE newsf1;" 2>/dev/null; then
        echo -e "  ${GREEN}●${NC} MySQL数据库"
        echo -e "    状态: ${GREEN}连接正常${NC}"
        
        # 获取表数量
        local table_count=$(mysql -h 127.0.0.1 -u newsf1 -pnewsf1 newsf1 -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='newsf1';" 2>/dev/null)
        echo -e "    表数: $table_count 张"
    else
        echo -e "  ${RED}●${NC} MySQL数据库"
        echo -e "    状态: ${RED}连接失败${NC}"
    fi
    echo ""
}

clear

print_title "Musk管理系统 - 服务状态"

echo -e "${BLUE}📊 服务运行状态：${NC}"
echo ""

# 检查后端
check_port $BACKEND_PORT "后端服务 (Webman)"

# 检查前端
check_port $FRONTEND_PORT "前端服务 (Vite)"

# 检查数据库
check_database

echo -e "${BLUE}📍 访问地址：${NC}"
echo -e "  前端: http://localhost:$FRONTEND_PORT"
echo -e "  后端: http://localhost:$BACKEND_PORT"
echo -e "  文档: http://localhost:$BACKEND_PORT/api-docs"
echo ""

echo -e "${BLUE}🔧 管理命令：${NC}"
echo -e "  启动: ${GREEN}./start.sh${NC}"
echo -e "  停止: ${RED}./stop.sh${NC}"
echo -e "  重启: ${YELLOW}./restart.sh${NC}"
echo -e "  状态: ${CYAN}./status.sh${NC}"
echo ""

