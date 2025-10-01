#!/bin/bash

################################################################################
# 项目一键启动脚本
# 功能：检查环境、启动后端服务、启动前端服务、显示访问信息
################################################################################

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

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

# 打印标题
print_title() {
    echo -e "${CYAN}"
    echo "════════════════════════════════════════════════════════════════"
    echo "  $1"
    echo "════════════════════════════════════════════════════════════════"
    echo -e "${NC}"
}

# 打印成功信息
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# 打印错误信息
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# 打印警告信息
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 打印信息
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# 打印步骤
print_step() {
    echo -e "${PURPLE}▶ $1${NC}"
}

# 检查命令是否存在
check_command() {
    if command -v $1 &> /dev/null; then
        print_success "$1 已安装"
        return 0
    else
        print_error "$1 未安装"
        return 1
    fi
}

# 检查端口是否被占用
check_port() {
    if lsof -Pi :$1 -sTCP:LISTEN -t >/dev/null 2>&1; then
        return 0  # 端口被占用
    else
        return 1  # 端口空闲
    fi
}

# 停止占用端口的进程
kill_port() {
    local port=$1
    local pids=$(lsof -ti:$port)
    if [ -n "$pids" ]; then
        print_warning "端口 $port 被占用，正在停止相关进程..."
        echo "$pids" | xargs kill -9 2>/dev/null || true
        sleep 1
        print_success "端口 $port 已释放"
    fi
}

################################################################################
# 环境检查
################################################################################

check_environment() {
    print_title "环境检查"
    
    local all_ok=true
    
    # 检查PHP
    print_step "检查 PHP..."
    if check_command php; then
        php_version=$(php -v | head -n 1 | cut -d " " -f 2)
        print_info "PHP 版本: $php_version"
    else
        all_ok=false
    fi
    
    # 检查Composer
    print_step "检查 Composer..."
    if check_command composer; then
        composer_version=$(composer --version | cut -d " " -f 3)
        print_info "Composer 版本: $composer_version"
    else
        all_ok=false
    fi
    
    # 检查MySQL
    print_step "检查 MySQL..."
    if check_command mysql; then
        mysql_version=$(mysql --version | cut -d " " -f 5 | cut -d "," -f 1)
        print_info "MySQL 版本: $mysql_version"
    else
        all_ok=false
    fi
    
    # 检查Node.js
    print_step "检查 Node.js..."
    if check_command node; then
        node_version=$(node --version)
        print_info "Node.js 版本: $node_version"
    else
        all_ok=false
    fi
    
    # 检查NPM
    print_step "检查 NPM..."
    if check_command npm; then
        npm_version=$(npm --version)
        print_info "NPM 版本: $npm_version"
    else
        all_ok=false
    fi
    
    echo ""
    
    if [ "$all_ok" = true ]; then
        print_success "所有环境检查通过！"
        return 0
    else
        print_error "环境检查失败，请安装缺失的依赖"
        return 1
    fi
}

################################################################################
# 数据库检查
################################################################################

check_database() {
    print_title "数据库检查"
    
    print_step "检查数据库连接..."
    
    # 从配置文件读取数据库信息
    DB_HOST="127.0.0.1"
    DB_PORT="3306"
    DB_NAME="newsf1"
    DB_USER="newsf1"
    DB_PASS="newsf1"
    
    # 测试数据库连接
    if mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        print_success "数据库连接成功"
        
        # 检查表是否存在
        table_count=$(mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';" 2>/dev/null)
        
        if [ "$table_count" -gt 0 ]; then
            print_success "数据库已初始化 (包含 $table_count 张表)"
        else
            print_warning "数据库为空，需要导入数据"
            read -p "是否现在导入数据库？(y/n) " -n 1 -r
            echo
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                import_database
            fi
        fi
    else
        print_error "数据库连接失败"
        print_info "请确保MySQL服务已启动，并且数据库配置正确"
        print_info "数据库配置: $DB_USER@$DB_HOST:$DB_PORT/$DB_NAME"
        return 1
    fi
}

################################################################################
# 导入数据库
################################################################################

import_database() {
    print_step "导入数据库..."
    
    if [ -f "$BACKEND_DIR/database/migrations/basic_migration.sql" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKEND_DIR/database/migrations/basic_migration.sql" 2>/dev/null
        print_success "数据库导入成功"
    else
        print_error "找不到迁移文件"
        return 1
    fi
}

################################################################################
# 安装依赖
################################################################################

install_dependencies() {
    print_title "安装依赖"
    
    # 后端依赖
    if [ -d "$BACKEND_DIR" ]; then
        print_step "检查后端依赖..."
        cd "$BACKEND_DIR"
        if [ ! -d "vendor" ]; then
            print_info "安装后端依赖..."
            composer install --no-dev --optimize-autoloader
            print_success "后端依赖安装完成"
        else
            print_success "后端依赖已安装"
        fi
        cd ..
    fi
    
    # 前端依赖
    if [ -d "$FRONTEND_DIR" ]; then
        print_step "检查前端依赖..."
        cd "$FRONTEND_DIR"
        if [ ! -d "node_modules" ]; then
            print_info "安装前端依赖..."
            npm install
            print_success "前端依赖安装完成"
        else
            print_success "前端依赖已安装"
        fi
        cd ..
    fi
}

################################################################################
# 启动后端服务
################################################################################

start_backend() {
    print_title "启动后端服务"
    
    # 检查端口
    if check_port $BACKEND_PORT; then
        print_warning "后端端口 $BACKEND_PORT 已被占用"
        read -p "是否停止占用进程并重启？(y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            kill_port $BACKEND_PORT
        else
            print_info "跳过后端服务启动"
            return 0
        fi
    fi
    
    print_step "启动 Webman 服务..."
    cd "$BACKEND_DIR"
    
    # 停止已运行的服务
    php start.php stop 2>/dev/null || true
    sleep 1
    
    # 启动服务
    php start.php start -d
    
    sleep 2
    
    # 检查服务状态
    if check_port $BACKEND_PORT; then
        print_success "后端服务启动成功"
        print_info "后端地址: http://localhost:$BACKEND_PORT"
        print_info "API文档: http://localhost:$BACKEND_PORT/api-docs"
    else
        print_error "后端服务启动失败"
        return 1
    fi
    
    cd ..
}

################################################################################
# 启动前端服务
################################################################################

start_frontend() {
    print_title "启动前端服务"
    
    # 检查端口
    if check_port $FRONTEND_PORT; then
        print_warning "前端端口 $FRONTEND_PORT 已被占用"
        read -p "是否停止占用进程并重启？(y/n) " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            kill_port $FRONTEND_PORT
        else
            print_info "跳过前端服务启动"
            return 0
        fi
    fi
    
    print_step "启动 Vite 开发服务器..."
    cd "$FRONTEND_DIR"
    
    # 后台启动前端服务
    nohup npm run dev > ../logs/frontend.log 2>&1 &
    FRONTEND_PID=$!
    
    echo $FRONTEND_PID > ../logs/frontend.pid
    
    print_info "等待前端服务启动..."
    sleep 5
    
    # 检查服务状态
    if check_port $FRONTEND_PORT; then
        print_success "前端服务启动成功"
        print_info "前端地址: http://localhost:$FRONTEND_PORT"
    else
        print_error "前端服务启动失败"
        print_info "请查看日志: logs/frontend.log"
        return 1
    fi
    
    cd ..
}

################################################################################
# 显示访问信息
################################################################################

show_info() {
    print_title "服务信息"
    
    echo -e "${GREEN}"
    echo "🎉 所有服务已启动成功！"
    echo ""
    echo "📍 访问地址："
    echo "   前端页面: http://localhost:$FRONTEND_PORT"
    echo "   后端API:  http://localhost:$BACKEND_PORT"
    echo "   API文档:  http://localhost:$BACKEND_PORT/api-docs"
    echo ""
    echo "🔐 默认账号："
    echo "   用户名: admin"
    echo "   密码:   password"
    echo ""
    echo "📝 日志文件："
    echo "   前端日志: logs/frontend.log"
    echo "   后端日志: webman-api/runtime/logs/webman.log"
    echo ""
    echo "⚠️  重要提示："
    echo "   - 首次部署后请立即修改默认密码"
    echo "   - 前端服务在后台运行，关闭终端不会停止"
    echo "   - 使用 ./stop.sh 停止所有服务"
    echo -e "${NC}"
}

################################################################################
# 主函数
################################################################################

main() {
    clear
    
    print_title "$PROJECT_NAME - 一键启动脚本"
    
    echo -e "${CYAN}开始启动服务...${NC}"
    echo ""
    
    # 创建日志目录
    mkdir -p logs
    
    # 1. 环境检查
    if ! check_environment; then
        exit 1
    fi
    
    echo ""
    
    # 2. 数据库检查
    if ! check_database; then
        print_warning "数据库检查失败，但继续启动服务"
    fi
    
    echo ""
    
    # 3. 安装依赖
    install_dependencies
    
    echo ""
    
    # 4. 启动后端
    if ! start_backend; then
        print_error "后端服务启动失败"
        exit 1
    fi
    
    echo ""
    
    # 5. 启动前端
    if ! start_frontend; then
        print_error "前端服务启动失败"
        exit 1
    fi
    
    echo ""
    
    # 6. 显示访问信息
    show_info
    
    # 7. 保持脚本运行（可选）
    echo ""
    print_info "按 Ctrl+C 退出（服务将继续在后台运行）"
    echo ""
    
    # 等待用户输入
    read -p "按 Enter 键打开浏览器..."
    
    # 尝试打开浏览器
    if command -v open &> /dev/null; then
        open "http://localhost:$FRONTEND_PORT"
    elif command -v xdg-open &> /dev/null; then
        xdg-open "http://localhost:$FRONTEND_PORT"
    elif command -v start &> /dev/null; then
        start "http://localhost:$FRONTEND_PORT"
    fi
}

################################################################################
# 执行主函数
################################################################################

main "$@"

