<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use think\facade\Db;

/**
 * 权限控制中间件
 * 基于Webman框架设计，集中式权限控制
 */
class PermissionMiddleware implements MiddlewareInterface
{
    /**
     * 路由权限映射表
     * 简化配置，直接映射路由到权限ID
     */
    private array $permissionMap = [
        // 角色管理
        'GET:/api/roles' => 231,           // 角色列表
        'POST:/api/roles' => 232,          // 角色添加
        'GET:/api/roles/{id}' => 231,      // 获取单个角色信息 (视为列表权限)
        'PUT:/api/roles/{id}' => 233,      // 角色编辑
        'DELETE:/api/roles/{id}' => 233,   // 角色删除 (与编辑权限相同)
        'GET:/api/roles/{id}/rights' => 234, // 获取角色权限
        'POST:/api/roles/{id}/rights' => 234, // 设置角色权限

        // 权限管理
        'GET:/api/permissions' => 251,     // 权限列表
        'POST:/api/permissions' => 252,    // 权限添加
        'GET:/api/permissions/{id}' => 251, // 获取单个权限信息 (视为列表权限)
        'PUT:/api/permissions/{id}' => 253, // 权限编辑
        'DELETE:/api/permissions/{id}' => 253, // 权限删除 (与编辑权限相同)

        // 操作日志管理
        'GET:/api/operation-logs' => 21,   // 操作日志查看

        // 管理员管理
        'GET:/api/admins' => 241,          // 管理员列表
        'POST:/api/admins' => 242,         // 管理员添加
        'GET:/api/admins/{id}' => 241,     // 获取单个管理员信息 (视为列表权限)
        'PUT:/api/admins/{id}' => 243,     // 管理员编辑
        'DELETE:/api/admins/{id}' => 243,  // 管理员删除 (与编辑权限相同)
        'POST:/api/admins/{id}/reset-password' => 243, // 重置管理员密码 (视为编辑权限)
        'POST:/api/admins/{id}/toggle-status' => 243,  // 切换管理员状态 (视为编辑权限)

        // 性能监控
        'GET:/api/performance/stats' => 22, // 性能状态查看
        'GET:/api/performance/slow-queries' => 22, // 慢查询查看
    ];

    /**
     * 公开API路由（需要JWT认证但无需特定权限验证）
     */
    private array $publicRoutes = [
        'GET:/api/me',
        'GET:/api/permissions/menu', // 菜单权限获取
        'GET:/api/roles/all-rights-tree', // 权限树获取
        'GET:/api/permissions/tree', // 权限树获取
        'GET:/api/dashboard/stats', // 仪表盘统计（所有登录用户可访问）
        'GET:/api/performance/stats', // 性能监控（所有登录用户可访问）
    ];

    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        $method = $request->method();
        $routeKey = "{$method}:{$path}";

        // 检查是否为公开路由
        if ($this->isPublicRoute($routeKey)) {
            return $handler($request);
        }

        // 获取用户信息
        $user = $request->user ?? null;
        if (!$user) {
            return $this->unauthorizedResponse('用户未登录');
        }

        // 检查所需权限
        $requiredPermission = $this->getRequiredPermission($routeKey, $path);
        if ($requiredPermission && !$this->hasPermission($user->user_id, $requiredPermission)) {
            return $this->forbiddenResponse('权限不足');
        }
        return $handler($request);
    }

    private function isPublicRoute(string $routeKey): bool
    {
        foreach ($this->publicRoutes as $publicRoute) {
            if ($this->matchRoute($routeKey, $publicRoute)) {
                return true;
            }
        }
        return false;
    }

    private function getRequiredPermission(string $routeKey, string $path): ?int
    {
        foreach ($this->permissionMap as $pattern => $permissionId) {
            // 检查是否是动态路由
            if (strpos($pattern, '{id}') !== false) {
                // 将 {id} 替换为正则表达式，以便匹配实际路径
                $regexPattern = str_replace('{id}', '(\d+)', $pattern);
                $regexPattern = str_replace('/', '\/', $regexPattern);
                if (preg_match('#^' . $regexPattern . '$#', $routeKey)) {
                    return $permissionId;
                }
            } elseif ($this->matchRoute($routeKey, $pattern)) {
                return $permissionId;
            }
        }
        return null; // 没有权限要求
    }

    private function matchRoute(string $routeKey, string $pattern): bool
    {
        if ($routeKey === $pattern) {
            return true;
        }
        // 处理通配符 * - 转换为正则表达式
        $regexPattern = str_replace('*', '[^/]+', $pattern);
        $regexPattern = str_replace('/', '\/', $regexPattern);
        return preg_match('#^' . $regexPattern . '$#', $routeKey);
    }

    private function hasPermission(int $userId, int $permissionId): bool
    {
        try {
            // 检查是否为超级管理员
            $isSuperAdmin = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role' => 'r'], 'ar.role_id = r.id')
                ->where('ar.admin_id', $userId)
                ->where('r.role_name', '超级管理员')
                ->count() > 0;

            if ($isSuperAdmin) {
                return true;
            }

            // 检查是否有指定权限
            $hasPermission = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role_right' => 'rr'], 'ar.role_id = rr.role_id')
                ->where('ar.admin_id', $userId)
                ->where('rr.right_id', $permissionId)
                ->count() > 0;

            return $hasPermission;
        } catch (\Exception $e) {
            error_log("检查权限失败: " . $e->getMessage());
            return false;
        }
    }

    private function unauthorizedResponse(string $message = '用户未登录'): Response
    {
        return response(json_encode([
            'code' => 401,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ]), 401, ['Content-Type' => 'application/json']);
    }

    private function forbiddenResponse(string $message = '权限不足'): Response
    {
        return response(json_encode([
            'code' => 403,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ]), 403, ['Content-Type' => 'application/json']);
    }
}
