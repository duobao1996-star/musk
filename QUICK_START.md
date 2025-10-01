# 🚀 快速启动指南

## 📋 一键启动脚本说明

本项目提供了4个便捷的管理脚本，让你可以轻松管理整个系统。

### 📜 可用脚本

| 脚本 | 功能 | 使用场景 |
|------|------|---------|
| `start.sh` | 🟢 一键启动 | 启动前端和后端服务 |
| `stop.sh` | 🔴 一键停止 | 停止所有运行的服务 |
| `restart.sh` | 🔄 一键重启 | 重启所有服务 |
| `status.sh` | 📊 查看状态 | 查看服务运行状态 |

---

## 🎯 快速开始

### 1️⃣ 首次启动（全新部署）

```bash
# 克隆项目（如果还没有）
git clone https://github.com/duobao1996-star/musk.git
cd musk

# 一键启动
./start.sh
```

**start.sh 会自动完成以下操作**：
- ✅ 检查运行环境（PHP、MySQL、Node.js等）
- ✅ 检查数据库连接和表结构
- ✅ 自动安装后端依赖（composer install）
- ✅ 自动安装前端依赖（npm install）
- ✅ 启动后端服务（Webman）
- ✅ 启动前端服务（Vite）
- ✅ 显示访问地址和默认账号

### 2️⃣ 日常使用

```bash
# 启动服务
./start.sh

# 查看服务状态
./status.sh

# 停止服务
./stop.sh

# 重启服务
./restart.sh
```

---

## 📊 start.sh - 启动脚本详解

### 功能特性

1. **环境检查** ✅
   - 检查 PHP、Composer、MySQL、Node.js、NPM
   - 显示各工具的版本信息
   - 检查端口占用情况

2. **数据库检查** 🗄️
   - 自动检查数据库连接
   - 检查表结构是否完整
   - 提供数据库导入选项

3. **依赖安装** 📦
   - 自动检测并安装后端依赖
   - 自动检测并安装前端依赖
   - 跳过已安装的依赖

4. **服务启动** 🚀
   - 智能处理端口占用
   - 后台启动前端服务
   - 实时显示启动状态

5. **信息展示** ℹ️
   - 显示访问地址
   - 显示默认账号
   - 显示日志位置

### 使用示例

```bash
# 基本启动
./start.sh

# 启动后会看到：
# ✅ 环境检查通过
# ✅ 数据库连接成功
# ✅ 后端依赖已安装
# ✅ 前端依赖已安装
# ✅ 后端服务启动成功
# ✅ 前端服务启动成功
# 
# 📍 访问地址：
#    前端: http://localhost:3001
#    后端: http://localhost:8787
#    文档: http://localhost:8787/api-docs
```

### 常见问题处理

**Q: 端口被占用怎么办？**  
A: 脚本会自动提示，询问是否停止占用进程并重启

**Q: 数据库未初始化？**  
A: 脚本会自动提示导入数据库

**Q: 依赖安装失败？**  
A: 脚本会显示错误信息，可以手动安装后重试

---

## 🔴 stop.sh - 停止脚本

### 功能

- 停止后端服务（Webman）
- 停止前端服务（Vite）
- 清理所有相关进程
- 释放端口占用

### 使用

```bash
./stop.sh

# 输出示例：
# ✅ Webman服务已停止
# ✅ Vite服务已停止
# ✅ 所有服务已停止
```

---

## 🔄 restart.sh - 重启脚本

### 功能

- 先停止所有服务
- 等待2秒确保服务完全停止
- 重新启动所有服务

### 使用

```bash
./restart.sh

# 相当于执行：
# ./stop.sh
# ./start.sh
```

---

## 📊 status.sh - 状态查看脚本

### 功能

- 显示后端服务状态
- 显示前端服务状态
- 显示数据库连接状态
- 显示服务端口和PID
- 显示访问地址

### 使用

```bash
./status.sh

# 输出示例：
# ════════════════════════════════════════
#   Musk管理系统 - 服务状态
# ════════════════════════════════════════
# 
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

## 🔧 环境要求

### 必需软件

| 软件 | 最低版本 | 推荐版本 |
|------|---------|---------|
| PHP | 8.2+ | 8.2.29 |
| MySQL | 8.0+ | 8.0+ |
| Node.js | 16+ | 18+ |
| Composer | 2.0+ | 最新版 |
| NPM | 8+ | 最新版 |

### 检查环境

```bash
# 检查 PHP
php -v

# 检查 MySQL
mysql --version

# 检查 Node.js
node --version

# 检查 NPM
npm --version

# 检查 Composer
composer --version
```

---

## 📝 配置说明

### 数据库配置

默认数据库配置位于 `webman-api/config/think-orm.php`：

```php
'hostname' => '127.0.0.1',
'database' => 'newsf1',
'username' => 'newsf1',
'password' => 'newsf1',
'hostport' => '3306',
```

### 端口配置

- **后端端口**: 8787（可在 `webman-api/config/server.php` 修改）
- **前端端口**: 3001（可在 `musk-admin/vite.config.ts` 修改）

### 修改端口

如果需要修改端口，需要同时修改：
1. 配置文件中的端口
2. 启动脚本中的端口变量

---

## 🔐 默认账号

启动后使用以下账号登录：

| 用户名 | 密码 | 角色 |
|--------|------|------|
| admin | password | 超级管理员 |
| testuser | password | Viewer角色 |
| testadmin1 | password | Admin角色 |

⚠️ **安全提示**: 首次部署后请立即修改默认密码！

---

## 📁 目录结构

```
musk/
├── start.sh              # 启动脚本
├── stop.sh               # 停止脚本
├── restart.sh            # 重启脚本
├── status.sh             # 状态查看脚本
├── logs/                 # 日志目录
│   ├── frontend.log      # 前端日志
│   └── frontend.pid      # 前端进程ID
├── webman-api/           # 后端代码
│   ├── runtime/logs/     # 后端日志
│   └── database/         # 数据库文件
└── musk-admin/           # 前端代码
```

---

## 🐛 故障排查

### 问题1: 启动失败

```bash
# 查看详细错误
cat logs/frontend.log
cat webman-api/runtime/logs/webman.log

# 检查端口占用
lsof -i:8787  # 后端端口
lsof -i:3001  # 前端端口

# 手动停止占用进程
kill -9 <PID>
```

### 问题2: 数据库连接失败

```bash
# 测试数据库连接
mysql -h 127.0.0.1 -u newsf1 -pnewsf1 newsf1

# 检查MySQL服务
# macOS
brew services list

# Linux
systemctl status mysql
```

### 问题3: 权限问题

```bash
# 给脚本添加执行权限
chmod +x start.sh stop.sh restart.sh status.sh

# 检查文件权限
ls -la *.sh
```

---

## 💡 高级用法

### 自定义启动选项

编辑 `start.sh` 修改以下变量：

```bash
BACKEND_PORT=8787     # 后端端口
FRONTEND_PORT=3001    # 前端端口
DB_HOST="127.0.0.1"   # 数据库主机
DB_NAME="newsf1"      # 数据库名称
```

### 生产环境部署

```bash
# 1. 修改配置为生产模式
vim webman-api/config/app.php
# 设置 debug => false

# 2. 构建前端
cd musk-admin
npm run build

# 3. 使用生产服务器（如Nginx）托管前端
# 4. 使用进程管理器（如Supervisor）管理后端
```

---

## 📞 帮助与支持

### 获取帮助

```bash
# 查看项目文档
cat DEPLOYMENT_GUIDE.md
cat COMPLETE_PROJECT_SUMMARY.md

# 查看数据库文档
cat webman-api/database/README.md
```

### 相关文档

- [部署指南](DEPLOYMENT_GUIDE.md)
- [项目总结](COMPLETE_PROJECT_SUMMARY.md)
- [数据库管理](webman-api/database/README.md)
- [深度审查](DEEP_PROJECT_REVIEW.md)

---

## 🎉 开始使用

现在你可以开始使用了：

```bash
# 1. 启动服务
./start.sh

# 2. 打开浏览器访问
# http://localhost:3001

# 3. 使用默认账号登录
# 用户名: admin
# 密码: password

# 4. 开始体验！
```

---

**祝你使用愉快！** 🚀

*最后更新: 2024年1月*
