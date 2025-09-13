# 🐳 Docker + 宝塔 + 自动推送部署方案

> 完整的自动化部署解决方案，支持Docker容器化部署和GitHub自动推送

## 🚀 方案概述

本方案提供三种部署方式：
1. **Docker容器化部署** - 使用Docker Compose一键部署
2. **宝塔服务器部署** - 直接在宝塔服务器上部署
3. **GitHub Actions自动部署** - 推送代码自动部署

## 📋 前置要求

### 服务器要求
- Linux服务器（推荐Ubuntu 20.04+）
- 宝塔面板已安装
- Docker和Docker Compose（可选）
- PHP 8.2+
- MySQL 8.0+
- Redis 6.0+（可选）

### 开发环境要求
- Git
- Docker Desktop（本地测试）
- GitHub账号

## 🐳 方案一：Docker容器化部署

### 1. 本地测试

```bash
# 克隆项目
git clone https://github.com/duobao1996-star/musk.git
cd musk

# 构建并启动所有服务
docker-compose up -d

# 查看服务状态
docker-compose ps

# 查看日志
docker-compose logs -f webman-api
```

### 2. 服务器部署

```bash
# 在服务器上创建项目目录
mkdir -p /www/wwwroot/webman-musk
cd /www/wwwroot/webman-musk

# 克隆项目
git clone https://github.com/duobao1996-star/musk.git .

# 配置环境变量
cp env.example .env
# 编辑.env文件配置数据库信息

# 启动服务
docker-compose up -d

# 设置开机自启
docker-compose up -d
```

### 3. 访问服务

- **API文档**: http://your-domain/api-docs
- **API接口**: http://your-domain/api
- **健康检查**: http://your-domain/api/health

## 🖥️ 方案二：宝塔服务器部署

### 1. 在宝塔面板中创建站点

1. 登录宝塔面板
2. **网站** → **添加站点**
3. 配置信息：
   - 域名：`musk.yourdomain.com`
   - 根目录：`/www/wwwroot/musk`
   - PHP版本：8.2
   - 数据库：MySQL 8.0

### 2. 配置Git部署

1. 进入站点设置
2. **Git部署** → **启用Git部署**
3. 配置：
   ```
   Git仓库: https://github.com/duobao1996-star/musk.git
   分支: main
   自动拉取: 开启
   拉取频率: 每小时
   ```

### 3. 手动部署

```bash
# 进入宝塔终端
cd /www/wwwroot/musk

# 克隆项目
git clone https://github.com/duobao1996-star/musk.git .

# 安装依赖
composer install --no-dev --optimize-autoloader

# 设置权限
chown -R www:www .
chmod -R 755 .
chmod -R 777 runtime/

# 配置环境
cp env.example .env
# 编辑.env文件

# 启动服务
php start.php start -d
```

### 4. 配置Nginx反向代理

在宝塔面板的站点配置中添加：

```nginx
location / {
    try_files $uri $uri/ @webman;
}

location @webman {
    proxy_pass http://127.0.0.1:8787;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    
    proxy_connect_timeout 60s;
    proxy_send_timeout 60s;
    proxy_read_timeout 60s;
}
```

## 🤖 方案三：GitHub Actions自动部署

### 1. 配置GitHub Secrets

在GitHub仓库设置中添加以下Secrets：

```
BAOTA_HOST=your_server_ip
BAOTA_USER=root
BAOTA_SSH_KEY=your_ssh_private_key
BAOTA_PORT=22
DOCKER_USERNAME=your_docker_username
DOCKER_PASSWORD=your_docker_password
```

### 2. 配置SSH密钥

```bash
# 在本地生成SSH密钥对
ssh-keygen -t rsa -b 4096 -C "your_email@example.com"

# 将公钥添加到宝塔服务器
ssh-copy-id root@your_server_ip

# 将私钥添加到GitHub Secrets
cat ~/.ssh/id_rsa
```

### 3. 自动部署流程

当您推送代码到`main`分支时，GitHub Actions会自动：

1. 运行测试
2. 构建Docker镜像
3. 部署到宝塔服务器
4. 执行健康检查
5. 发送部署结果通知

## 🔧 自动部署脚本使用

### 1. 宝塔自动部署脚本

```bash
# 下载部署脚本
wget https://raw.githubusercontent.com/duobao1996-star/musk/main/docker/baota-deploy.sh
chmod +x baota-deploy.sh

# 部署最新版本
./baota-deploy.sh deploy

# 回滚到上一个版本
./baota-deploy.sh rollback

# 检查服务状态
./baota-deploy.sh status

# 查看部署日志
./baota-deploy.sh logs
```

### 2. Docker自动部署

```bash
# 启动自动部署监控
docker-compose up -d auto-deploy

# 查看部署日志
docker-compose logs -f auto-deploy

# 手动触发部署
docker exec webman-auto-deploy /usr/local/bin/auto-deploy.sh deploy
```

## 📊 监控和维护

### 1. 服务监控

```bash
# 检查服务状态
docker-compose ps

# 查看资源使用
docker stats

# 查看日志
docker-compose logs -f
```

### 2. 日志管理

```bash
# 查看应用日志
tail -f runtime/logs/webman.log

# 查看Nginx日志
tail -f /www/wwwlogs/musk.access.log

# 查看部署日志
tail -f /var/log/webman-deploy.log
```

### 3. 备份策略

```bash
# 自动备份（每天凌晨2点）
0 2 * * * /www/wwwroot/musk/docker/baota-deploy.sh backup

# 清理旧备份（保留7天）
0 3 * * * find /www/backup -name "*.tar.gz" -mtime +7 -delete
```

## 🚨 故障排除

### 1. 服务无法启动

```bash
# 检查端口占用
netstat -tlnp | grep 8787

# 检查进程
ps aux | grep webman

# 查看错误日志
tail -f runtime/logs/error.log
```

### 2. 数据库连接失败

```bash
# 检查数据库配置
cat .env | grep DB_

# 测试数据库连接
mysql -h127.0.0.1 -unewsf1 -pnewsf1 newsf1
```

### 3. 权限问题

```bash
# 重新设置权限
chown -R www:www /www/wwwroot/musk
chmod -R 755 /www/wwwroot/musk
chmod -R 777 /www/wwwroot/musk/runtime
```

## 📈 性能优化

### 1. Docker优化

```yaml
# docker-compose.yml 中的优化配置
services:
  webman-api:
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
```

### 2. Nginx优化

```nginx
# 启用gzip压缩
gzip on;
gzip_types text/plain text/css application/json application/javascript;

# 设置缓存
location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### 3. PHP优化

```ini
; php.ini 优化配置
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

## 🔒 安全配置

### 1. SSL证书

```bash
# 使用Let's Encrypt免费证书
certbot --nginx -d musk.yourdomain.com
```

### 2. 防火墙配置

```bash
# 只开放必要端口
ufw allow 22    # SSH
ufw allow 80    # HTTP
ufw allow 443   # HTTPS
ufw enable
```

### 3. 数据库安全

```sql
-- 创建专用数据库用户
CREATE USER 'webman_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON newsf1.* TO 'webman_user'@'localhost';
FLUSH PRIVILEGES;
```

## 📞 技术支持

如果遇到问题，可以：

1. 查看项目文档：[README.md](README.md)
2. 查看API文档：http://your-domain/api-docs
3. 提交Issue：[GitHub Issues](https://github.com/duobao1996-star/musk/issues)
4. 查看部署日志：`docker-compose logs`

---

🎉 **恭喜！您已经完成了Docker + 宝塔 + 自动推送的完整部署方案！**
