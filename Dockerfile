# Webman API 2.0 Docker 镜像
FROM php:8.2-cli

# 设置工作目录
WORKDIR /app

# 安装系统依赖
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    supervisor \
    cron \
    rsync \
    && rm -rf /var/lib/apt/lists/*

# 安装PHP扩展
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 安装Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制项目文件
COPY . .

# 安装依赖
RUN composer install --no-dev --optimize-autoloader

# 设置权限
RUN chown -R www-data:www-data /app \
    && chmod -R 755 /app \
    && mkdir -p /app/runtime/logs \
    && chmod -R 777 /app/runtime

# 创建环境配置文件
RUN cp env.example .env

# 复制supervisor配置
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 复制自动部署脚本
COPY docker/auto-deploy.sh /usr/local/bin/auto-deploy.sh
RUN chmod +x /usr/local/bin/auto-deploy.sh

# 复制cron任务
COPY docker/crontab /etc/cron.d/webman-cron
RUN chmod 0644 /etc/cron.d/webman-cron

# 暴露端口
EXPOSE 8787

# 启动命令
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
