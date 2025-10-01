# 🎉 一键启动脚本部署完成

## ✅ 已创建的脚本

| 脚本文件 | 功能说明 | 权限 | 状态 |
|---------|---------|------|------|
| `start.sh` | 🟢 一键启动所有服务 | ✅ 可执行 | ✅ 已推送 |
| `stop.sh` | 🔴 一键停止所有服务 | ✅ 可执行 | ✅ 已推送 |
| `restart.sh` | 🔄 一键重启所有服务 | ✅ 可执行 | ✅ 已推送 |
| `status.sh` | 📊 查看服务运行状态 | ✅ 可执行 | ✅ 已推送 |
| `QUICK_START.md` | 📖 快速开始指南 | - | ✅ 已推送 |

---

## 🚀 快速使用指南

### 最简单的启动方式

```bash
# 1. 克隆项目
git clone https://github.com/duobao1996-star/musk.git
cd musk

# 2. 一键启动
./start.sh

# 3. 打开浏览器
# http://localhost:3001
```

就是这么简单！✨

---

## 📋 脚本功能详解

### 🟢 start.sh - 启动脚本

#### 自动化步骤
1. ✅ **环境检查**
   - PHP 8.2+
   - MySQL 8.0+
   - Node.js 16+
   - Composer
   - NPM

2. ✅ **数据库检查**
   - 测试连接
   - 检查表结构
   - 提供导入选项

3. ✅ **依赖管理**
   - 自动安装后端依赖（composer）
   - 自动安装前端依赖（npm）
   - 跳过已安装的依赖

4. ✅ **服务启动**
   - 启动 Webman 后端（端口8787）
   - 启动 Vite 前端（端口3001）
   - 智能处理端口占用

5. ✅ **信息展示**
   - 访问地址
   - 默认账号
   - 日志位置

#### 使用示例
```bash
./start.sh

# 输出示例：
# ════════════════════════════════════════
#   环境检查
# ════════════════════════════════════════
# ✅ PHP 已安装
# ℹ️  PHP 版本: 8.2.29
# ✅ Composer 已安装
# ✅ MySQL 已安装
# ✅ Node.js 已安装
# ✅ NPM 已安装
# 
# ✅ 所有环境检查通过！
# 
# ════════════════════════════════════════
#   数据库检查
# ════════════════════════════════════════
# ✅ 数据库连接成功
# ✅ 数据库已初始化 (包含 12 张表)
# 
# ════════════════════════════════════════
#   启动后端服务
# ════════════════════════════════════════
# ✅ 后端服务启动成功
# ℹ️  后端地址: http://localhost:8787
# 
# ════════════════════════════════════════
#   启动前端服务
# ════════════════════════════════════════
# ✅ 前端服务启动成功
# ℹ️  前端地址: http://localhost:3001
```

---

### 🔴 stop.sh - 停止脚本

#### 功能
- 停止 Webman 后端服务
- 停止 Vite 前端服务
- 清理所有相关进程
- 释放端口占用

#### 使用示例
```bash
./stop.sh

# 输出：
# ════════════════════════════════════════
#   停止所有服务
# ════════════════════════════════════════
# ✅ Webman服务已停止
# ✅ Vite服务已停止
# ✅ 所有服务已停止
```

---

### 🔄 restart.sh - 重启脚本

#### 功能
- 执行 stop.sh
- 等待2秒
- 执行 start.sh

#### 使用示例
```bash
./restart.sh

# 相当于：
# ./stop.sh && sleep 2 && ./start.sh
```

---

### 📊 status.sh - 状态查看

#### 显示信息
- ✅ 后端服务状态（运行/停止）
- ✅ 前端服务状态（运行/停止）
- ✅ 数据库连接状态
- ✅ 服务端口和进程ID
- ✅ 表数量统计
- ✅ 访问地址
- ✅ 管理命令提示

#### 使用示例
```bash
./status.sh

# 输出：
# 📊 服务运行状态：
# 
#   ● 后端服务 (Webman)
#     状态: 运行中
#     端口: 8787
#     PID:  12345
# 
#   ● 前端服务 (Vite)
#     状态: 运行中
#     端口: 3001
#     PID:  12346
# 
#   ● MySQL数据库
#     状态: 连接正常
#     表数: 12 张
```

---

## 🎯 实际使用场景

### 场景1: 开发环境快速启动
```bash
# 早上开始工作
./start.sh

# 开始开发...

# 下班前停止服务
./stop.sh
```

### 场景2: 代码更新后重启
```bash
# 拉取最新代码
git pull

# 重启服务
./restart.sh
```

### 场景3: 检查服务状态
```bash
# 快速查看服务是否运行
./status.sh
```

### 场景4: 新环境部署
```bash
# 克隆项目
git clone https://github.com/duobao1996-star/musk.git
cd musk

# 一键启动（自动完成所有配置）
./start.sh

# 完成！
```

---

## 🌟 脚本特色功能

### 1. 智能环境检查
- 自动检测所有必需的软件
- 显示版本信息
- 给出明确的错误提示

### 2. 智能端口管理
- 自动检测端口占用
- 询问是否停止占用进程
- 安全地重启服务

### 3. 彩色输出
- 🟢 成功信息（绿色）
- 🔴 错误信息（红色）
- 🟡 警告信息（黄色）
- 🔵 普通信息（蓝色）

### 4. 友好的交互
- 清晰的进度提示
- 明确的操作选项
- 详细的错误说明

### 5. 后台运行
- 前端服务后台运行
- 关闭终端不影响服务
- 使用PID文件管理进程

---

## 📁 相关文件

### 日志文件
```
logs/
├── frontend.log       # 前端运行日志
└── frontend.pid       # 前端进程ID

webman-api/runtime/logs/
└── webman.log        # 后端运行日志
```

### 配置文件
```
webman-api/config/
├── think-orm.php     # 数据库配置
├── server.php        # 服务器配置
└── app.php           # 应用配置

musk-admin/
└── vite.config.ts    # 前端配置
```

---

## 🔧 自定义配置

### 修改端口

编辑脚本文件修改端口：

```bash
# start.sh, stop.sh, restart.sh, status.sh
BACKEND_PORT=8787     # 改为你想要的后端端口
FRONTEND_PORT=3001    # 改为你想要的前端端口
```

同时修改配置文件：

```bash
# 后端端口: webman-api/config/server.php
'listen' => 'http://0.0.0.0:8787'

# 前端端口: musk-admin/vite.config.ts
server: {
  port: 3001
}
```

### 修改数据库配置

编辑 `webman-api/config/think-orm.php`：

```php
'hostname' => '127.0.0.1',    // 数据库主机
'database' => 'newsf1',        // 数据库名
'username' => 'newsf1',        // 用户名
'password' => 'newsf1',        // 密码
'hostport' => '3306',          // 端口
```

---

## 🐛 常见问题

### Q1: 启动脚本报错 "command not found"
```bash
# 添加执行权限
chmod +x start.sh stop.sh restart.sh status.sh

# 或者使用 bash 运行
bash start.sh
```

### Q2: 端口被占用
```bash
# 查看端口占用
lsof -i:8787  # 后端
lsof -i:3001  # 前端

# 停止占用进程
kill -9 <PID>

# 或者让脚本自动处理
# start.sh 会提示是否停止占用进程
```

### Q3: 数据库连接失败
```bash
# 检查MySQL服务
# macOS
brew services list

# Linux
systemctl status mysql

# 测试连接
mysql -h 127.0.0.1 -u newsf1 -pnewsf1 newsf1
```

### Q4: 前端服务启动失败
```bash
# 查看前端日志
cat logs/frontend.log

# 手动启动前端
cd musk-admin
npm run dev
```

### Q5: 后端服务启动失败
```bash
# 查看后端日志
cat webman-api/runtime/logs/webman.log

# 检查PHP版本
php -v  # 需要 8.2+

# 手动启动后端
cd webman-api
php start.php start
```

---

## 📊 性能监控

### 查看服务资源占用

```bash
# 查看进程资源占用
ps aux | grep webman
ps aux | grep vite

# 查看端口连接数
netstat -an | grep 8787
netstat -an | grep 3001

# 实时监控
top  # 按 PID 查找
```

### 日志管理

```bash
# 实时查看日志
tail -f logs/frontend.log
tail -f webman-api/runtime/logs/webman.log

# 清理日志
> logs/frontend.log
> webman-api/runtime/logs/webman.log
```

---

## 🎉 总结

### ✅ 已完成
- ✅ 创建了4个管理脚本
- ✅ 所有脚本都有执行权限
- ✅ 已推送到Git仓库
- ✅ 编写了详细的文档

### 🎯 使用优势
- **简单**: 一个命令搞定所有启动步骤
- **智能**: 自动检查环境和依赖
- **安全**: 智能处理端口占用
- **友好**: 彩色输出和详细提示
- **高效**: 节省大量手动操作时间

### 🚀 现在可以
```bash
# 任何时候，只需要
./start.sh

# 就能启动整个系统！
```

---

**一键启动脚本让项目部署和管理变得超级简单！** 🎉

*创建时间: 2024年1月*  
*最后更新: 2024年1月*
