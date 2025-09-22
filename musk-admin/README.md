# Musk管理系统前端

基于 Vue 3 + TypeScript + Element Plus 构建的现代化管理后台系统。

## 功能特性

- 🔐 JWT认证授权
- 👥 基于角色的权限控制(RBAC)
- 📊 操作日志记录与统计
- 📈 性能监控
- 🎨 现代化UI设计
- 📱 响应式布局
- 🏷️ 标签页导航

## 技术栈

- **框架**: Vue 3 + TypeScript
- **构建工具**: Vite
- **UI组件**: Element Plus
- **状态管理**: Pinia
- **路由**: Vue Router
- **HTTP客户端**: Axios
- **图标**: Element Plus Icons

## 快速开始

### 1. 环境要求

- Node.js 16+
- npm 或 pnpm

### 2. 安装依赖

```bash
npm install
```

### 3. 启动开发服务器

```bash
npm run dev
```

前端服务将在 `http://localhost:3001` 启动。

### 4. 构建生产版本

```bash
npm run build
```

## 默认账户

| 用户名 | 密码 | 角色 | 权限 |
|--------|------|------|------|
| admin | Admin@12345 | Super Admin | 所有权限 |

## 页面功能

### 仪表盘
- 系统统计信息
- 快速操作入口
- 系统健康状态

### 角色管理
- 角色列表查看
- 新增/编辑/删除角色
- 角色权限分配

### 权限管理
- 权限树形结构展示
- 菜单权限与接口权限管理
- 权限的新增/编辑/删除

### 操作日志
- 操作日志查看与搜索
- 日志统计信息
- 日志清理功能

### 性能监控
- 系统性能统计
- 慢查询监控
- 内存使用情况

## API接口

前端通过以下API与后端通信：

- 认证接口: `/api/login`, `/api/logout`, `/api/me`
- 角色管理: `/api/roles/*`
- 权限管理: `/api/permissions/*`
- 操作日志: `/api/operation-logs/*`
- 性能监控: `/api/performance/*`

## 项目结构

```
src/
├── api/           # API接口定义
├── components/    # 公共组件
├── layouts/       # 布局组件
├── router/        # 路由配置
├── stores/        # Pinia状态管理
├── utils/         # 工具函数
├── views/         # 页面组件
└── main.ts        # 应用入口
```

## 开发说明

1. 所有API请求都通过 `src/utils/request.ts` 统一处理
2. 认证状态通过 Pinia store 管理
3. 路由守卫确保页面访问权限
4. 响应式设计支持移动端访问

## 浏览器支持

- Chrome >= 87
- Firefox >= 78
- Safari >= 14
- Edge >= 88