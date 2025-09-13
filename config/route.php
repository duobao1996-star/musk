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
    // API首页
    Route::get('/', [app\controller\ApiController::class, 'index']);
    
    // 认证相关路由（不需要JWT认证）
    Route::post('/login', [app\controller\AuthController::class, 'login']);
    Route::post('/register', [app\controller\AuthController::class, 'register']);
    Route::post('/refresh-token', [app\controller\AuthController::class, 'refreshToken']);
    
    // 需要认证的路由
    Route::group('', function () {
        // 用户相关API
        Route::get('/users', [app\controller\ApiController::class, 'users']);
        Route::get('/user/{id}', [app\controller\ApiController::class, 'user']);
        Route::post('/user', [app\controller\ApiController::class, 'createUser']);
        Route::put('/user/{id}', [app\controller\ApiController::class, 'updateUser']);
        Route::delete('/user/{id}', [app\controller\ApiController::class, 'deleteUser']);
        
        // 认证相关API
        Route::get('/me', [app\controller\AuthController::class, 'me']);
        Route::post('/logout', [app\controller\AuthController::class, 'logout']);
        
        // 管理员模块
        Route::group('/admin', function () {
            Route::get('/', [app\controller\AdminController::class, 'index']);
            Route::get('/stats', [app\controller\AdminController::class, 'stats']);
            Route::get('/{id}', [app\controller\AdminController::class, 'show']);
            Route::post('/', [app\controller\AdminController::class, 'store']);
            Route::put('/{id}', [app\controller\AdminController::class, 'update']);
            Route::delete('/{id}', [app\controller\AdminController::class, 'destroy']);
        });
        
        // 商户/代理模块
        Route::group('/merchant', function () {
            Route::get('/', [app\controller\MerchantController::class, 'index']);
            Route::get('/stats', [app\controller\MerchantController::class, 'stats']);
            Route::get('/{id}', [app\controller\MerchantController::class, 'show']);
            Route::post('/', [app\controller\MerchantController::class, 'store']);
            Route::put('/{id}', [app\controller\MerchantController::class, 'update']);
            Route::delete('/{id}', [app\controller\MerchantController::class, 'destroy']);
            Route::post('/{id}/reset-password', [app\controller\MerchantController::class, 'resetPassword']);
            Route::post('/{id}/toggle-status', [app\controller\MerchantController::class, 'toggleStatus']);
        });
        
        // 操作日志模块
        Route::group('/logs', function () {
            Route::get('/', [app\controller\OperationLogController::class, 'index']);
            Route::get('/stats', [app\controller\OperationLogController::class, 'stats']);
            Route::get('/login', [app\controller\OperationLogController::class, 'loginLogs']);
            Route::get('/types', [app\controller\OperationLogController::class, 'operationTypes']);
            Route::get('/modules', [app\controller\OperationLogController::class, 'operationModules']);
            Route::get('/rights', [app\controller\OperationLogController::class, 'rights']);
            Route::get('/right-by-url', [app\controller\OperationLogController::class, 'getRightByUrl']);
            Route::post('/sync-descriptions', [app\controller\OperationLogController::class, 'syncRightDescriptions']);
            Route::get('/{id}', [app\controller\OperationLogController::class, 'show']);
            Route::post('/clean', [app\controller\OperationLogController::class, 'clean']);
        });

        // 性能监控模块
        Route::group('/performance', function () {
            Route::get('/status', [app\controller\PerformanceController::class, 'status']);
            Route::post('/clear-cache', [app\controller\PerformanceController::class, 'clearCache']);
            Route::get('/slow-queries', [app\controller\PerformanceController::class, 'slowQueries']);
        });

        // 权限管理模块
        Route::group('/permissions', function () {
            Route::get('/', [app\controller\PermissionController::class, 'index']);
            Route::get('/tree', [app\controller\PermissionController::class, 'tree']);
            Route::get('/menu', [app\controller\PermissionController::class, 'menu']);
            Route::get('/stats', [app\controller\PermissionController::class, 'stats']);
            Route::get('/{id}', [app\controller\PermissionController::class, 'show']);
            Route::post('/', [app\controller\PermissionController::class, 'store']);
            Route::put('/{id}', [app\controller\PermissionController::class, 'update']);
            Route::delete('/{id}', [app\controller\PermissionController::class, 'destroy']);
            Route::post('/batch-delete', [app\controller\PermissionController::class, 'batchDelete']);
        });

        // 角色管理模块
        Route::group('/roles', function () {
            Route::get('/', [app\controller\RoleController::class, 'index']);
            Route::get('/stats', [app\controller\RoleController::class, 'stats']);
            Route::get('/all-rights-tree', [app\controller\RoleController::class, 'allRightsTree']);
            Route::post('/', [app\controller\RoleController::class, 'store']);
            Route::post('/batch-delete', [app\controller\RoleController::class, 'batchDelete']);
            
            // 角色详情和权限管理（放在最后，避免路由冲突）
            Route::get('/{id}', [app\controller\RoleController::class, 'show']);
            Route::put('/{id}', [app\controller\RoleController::class, 'update']);
            Route::delete('/{id}', [app\controller\RoleController::class, 'destroy']);
            Route::get('/{id}/rights', [app\controller\RoleController::class, 'rights']);
            Route::post('/{id}/rights', [app\controller\RoleController::class, 'setRights']);
            Route::get('/{id}/rights-tree', [app\controller\RoleController::class, 'rightsTree']);
        });

        // 软删除管理模块
        Route::group('/soft-delete', function () {
            // 获取已删除数据列表
            Route::get('/admins', [app\controller\SoftDeleteController::class, 'getDeletedAdmins']);
            Route::get('/users', [app\controller\SoftDeleteController::class, 'getDeletedUsers']);
            Route::get('/rights', [app\controller\SoftDeleteController::class, 'getDeletedRights']);
            Route::get('/logs', [app\controller\SoftDeleteController::class, 'getDeletedLogs']);
            
            // 恢复数据
            Route::post('/restore/admin', [app\controller\SoftDeleteController::class, 'restoreAdmin']);
            Route::post('/restore/user', [app\controller\SoftDeleteController::class, 'restoreUser']);
            Route::post('/restore/right', [app\controller\SoftDeleteController::class, 'restoreRight']);
            Route::post('/restore/log', [app\controller\SoftDeleteController::class, 'restoreLog']);
            
            // 永久删除数据
            Route::post('/force-delete/admin', [app\controller\SoftDeleteController::class, 'forceDeleteAdmin']);
            Route::post('/force-delete/user', [app\controller\SoftDeleteController::class, 'forceDeleteUser']);
            Route::post('/force-delete/right', [app\controller\SoftDeleteController::class, 'forceDeleteRight']);
            Route::post('/force-delete/log', [app\controller\SoftDeleteController::class, 'forceDeleteLog']);
            
            // 批量操作
            Route::post('/batch-restore/logs', [app\controller\SoftDeleteController::class, 'batchRestoreLogs']);
            Route::post('/batch-force-delete/logs', [app\controller\SoftDeleteController::class, 'batchForceDeleteLogs']);
            
            // 清理软删除数据
            Route::post('/cleanup', [app\controller\SoftDeleteController::class, 'cleanupSoftDeletedData']);
        });
    })->middleware([app\middleware\JwtMiddleware::class, app\middleware\OperationLogMiddleware::class]);
});

// 默认路由
Route::get('/', [app\controller\IndexController::class, 'index']);
Route::get('/view', [app\controller\IndexController::class, 'view']);
Route::get('/json', [app\controller\IndexController::class, 'json']);
Route::get('/api-docs', [app\controller\IndexController::class, 'apiDocs']);


