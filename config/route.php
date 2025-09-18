<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// API路由组
Route::group('/api', function () {
    // 健康检查
    Route::get('/', [app\controller\ApiController::class, 'index']);
    Route::get('/health', [app\controller\ApiController::class, 'health']);
    Route::get('/ready', [app\controller\ApiController::class, 'ready']);
    
    // 认证相关路由（不需要JWT认证）
    Route::post('/login', [app\controller\AuthController::class, 'login']);
    Route::post('/refresh-token', [app\controller\AuthController::class, 'refreshToken']);
    Route::post('/logout', [app\controller\AuthController::class, 'logout']);
    
    // 需要JWT认证的路由
    Route::get('/me', [app\controller\AuthController::class, 'me'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    
    // 角色管理路由
    Route::get('/roles', [app\controller\RoleController::class, 'index'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::post('/roles', [app\controller\RoleController::class, 'store'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/roles/all-rights-tree', [app\controller\RoleController::class, 'allRightsTree'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/roles/{id}', [app\controller\RoleController::class, 'show'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::put('/roles/{id}', [app\controller\RoleController::class, 'update'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::delete('/roles/{id}', [app\controller\RoleController::class, 'destroy'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/roles/{id}/rights', [app\controller\RoleController::class, 'rights'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::post('/roles/{id}/rights', [app\controller\RoleController::class, 'setRights'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);

    // 权限管理路由
    Route::get('/permissions', [app\controller\PermissionController::class, 'index'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::post('/permissions', [app\controller\PermissionController::class, 'store'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/permissions/tree', [app\controller\PermissionController::class, 'tree'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/permissions/menu', [app\controller\PermissionController::class, 'menu'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/permissions/{id}', [app\controller\PermissionController::class, 'show'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::put('/permissions/{id}', [app\controller\PermissionController::class, 'update'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::delete('/permissions/{id}', [app\controller\PermissionController::class, 'destroy'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);

    // 操作日志路由
    Route::get('/operation-logs', [app\controller\OperationLogController::class, 'index'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/operation-logs/stats', [app\controller\OperationLogController::class, 'stats'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/operation-logs/login', [app\controller\OperationLogController::class, 'loginLogs'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::post('/operation-logs/clean', [app\controller\OperationLogController::class, 'clean'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);

    // 软删除日志回收站
    Route::get('/soft-delete/logs', [app\controller\OperationLogController::class, 'deletedLogs'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::post('/soft-delete/logs/restore', [app\controller\OperationLogController::class, 'restore'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::delete('/soft-delete/logs/force', [app\controller\OperationLogController::class, 'forceDelete'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    // 兼容别名：/soft-delete/force-delete/log（保留DELETE，移除POST混用）
    Route::delete('/soft-delete/force-delete/log', [app\controller\OperationLogController::class, 'forceDelete'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    // 回收站清理
    Route::post('/soft-delete/cleanup', [app\controller\OperationLogController::class, 'cleanup'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);

    // 性能监控路由
    Route::get('/performance/stats', [app\controller\PerformanceController::class, 'status'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
    Route::get('/performance/slow-queries', [app\controller\PerformanceController::class, 'slowQueries'])->middleware([
        app\middleware\JwtMiddleware::class
    ]);
});

// 文档路由
Route::get('/api-docs', [app\controller\IndexController::class, 'apiDocs']);