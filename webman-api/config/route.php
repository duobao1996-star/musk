<?php
/**
 * 路由配置文件
 * 使用Webman框架的路由组特性进行权限控制
 */

use Webman\Route;

// 公开路由（无需认证）
Route::post('/api/login', [app\controller\AuthController::class, 'login']);
Route::post('/api/logout', [app\controller\AuthController::class, 'logout']);

// 健康检查路由
Route::get('/api/health', [app\controller\ApiController::class, 'health']);
Route::get('/api/ready', [app\controller\ApiController::class, 'ready']);
Route::get('/api', [app\controller\ApiController::class, 'index']);

// 公开API路由（需要JWT认证但无需权限验证）
Route::get('/api/me', [app\controller\AuthController::class, 'me'])->middleware([
    app\middleware\JwtMiddleware::class
]);
Route::get('/api/permissions/menu', [app\controller\PermissionController::class, 'menu'])->middleware([
    app\middleware\JwtMiddleware::class
]);
Route::get('/api/roles/all-rights-tree', [app\controller\RoleController::class, 'allRightsTree'])->middleware([
    app\middleware\JwtMiddleware::class
]);
Route::get('/api/permissions/tree', [app\controller\PermissionController::class, 'tree'])->middleware([
    app\middleware\JwtMiddleware::class
]);

// 仪表盘统计（所有登录用户可访问）
Route::get('/api/dashboard/stats', [app\controller\ApiController::class, 'dashboardStats'])->middleware([
    app\middleware\JwtMiddleware::class
]);

// 需要权限验证的管理接口
// 角色管理
Route::get('/api/roles', [app\controller\RoleController::class, 'index'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/roles', [app\controller\RoleController::class, 'store'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/roles/{id}', [app\controller\RoleController::class, 'show'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::put('/api/roles/{id}', [app\controller\RoleController::class, 'update'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::delete('/api/roles/{id}', [app\controller\RoleController::class, 'destroy'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/roles/{id}/rights', [app\controller\RoleController::class, 'rights'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/roles/{id}/rights', [app\controller\RoleController::class, 'setRights'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);

// 权限管理
Route::get('/api/permissions', [app\controller\PermissionController::class, 'index'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/permissions', [app\controller\PermissionController::class, 'store'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/permissions/{id}', [app\controller\PermissionController::class, 'show'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::put('/api/permissions/{id}', [app\controller\PermissionController::class, 'update'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::delete('/api/permissions/{id}', [app\controller\PermissionController::class, 'destroy'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);

// 操作日志管理
Route::get('/api/operation-logs', [app\controller\OperationLogController::class, 'index'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);

// 管理员管理
Route::get('/api/admins', [app\controller\AdminController::class, 'index'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/admins', [app\controller\AdminController::class, 'store'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
// 管理员管理 - 具体路由必须在参数路由之前
Route::post('/api/admins/batch-create', [app\controller\AdminController::class, 'batchCreate'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/admins/options', [app\controller\AdminController::class, 'options'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/admins/stats', [app\controller\AdminController::class, 'stats'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/admins/{id}', [app\controller\AdminController::class, 'show'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::put('/api/admins/{id}', [app\controller\AdminController::class, 'update'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::delete('/api/admins/{id}', [app\controller\AdminController::class, 'destroy'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/admins/{id}/reset-password', [app\controller\AdminController::class, 'resetPassword'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/admins/{id}/toggle-status', [app\controller\AdminController::class, 'toggleStatus'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);

// 系统管理
Route::get('/api/system/info', [app\controller\SystemController::class, 'info'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/system/config', [app\controller\SystemController::class, 'config'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/system/config', [app\controller\SystemController::class, 'updateConfig'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/system/status', [app\controller\SystemController::class, 'status'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/system/health', [app\controller\SystemController::class, 'health'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::post('/api/system/clear-cache', [app\controller\SystemController::class, 'clearCache'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);

// 性能监控
Route::get('/api/performance/stats', [app\controller\PerformanceController::class, 'status'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);
Route::get('/api/performance/slow-queries', [app\controller\PerformanceController::class, 'slowQueries'])->middleware([
    app\middleware\JwtMiddleware::class,
    app\middleware\PermissionMiddleware::class
]);