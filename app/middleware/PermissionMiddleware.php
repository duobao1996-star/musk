<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\model\Right;
use app\model\RoleRight;

class PermissionMiddleware implements MiddlewareInterface
{
    private $rightModel;
    private $roleRightModel;

    public function __construct()
    {
        $this->rightModel = new Right();
        $this->roleRightModel = new RoleRight();
    }

    public function process(Request $request, callable $handler): Response
    {
        // 获取当前用户信息
        $user = $request->user ?? null;
        
        if (!$user) {
            return json([
                'code' => 401,
                'message' => '用户未登录',
                'data' => null
            ], 401);
        }

        // 超级管理员拥有所有权限
        if ($user['role_id'] == 1) {
            return $handler($request);
        }

        // 获取请求的路径和方法
        $path = $request->path();
        $method = $request->method();

        // 标准化路径
        $normalizedPath = $this->normalizePath($path);

        // 获取对应的权限
        $right = $this->rightModel->getRightByPath($normalizedPath, $method);
        
        if (!$right) {
            // 如果没有找到对应权限，允许访问（可能是新接口还未配置权限）
            return $handler($request);
        }

        // 检查用户角色是否有该权限
        $hasPermission = $this->roleRightModel->hasRight($user['role_id'], $right['id']);
        
        if (!$hasPermission) {
            return json([
                'code' => 403,
                'message' => '权限不足',
                'data' => null
            ], 403);
        }

        return $handler($request);
    }

    /**
     * 标准化路径
     */
    private function normalizePath($path)
    {
        // 移除查询参数
        $path = strtok($path, '?');
        
        // 移除ID参数，用占位符替换
        $path = preg_replace('/\/\d+$/', '', $path);
        $path = preg_replace('/\/\d+\//', '/', $path);
        
        // 移除结尾的斜杠（除了根路径）
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        
        return $path;
    }

    /**
     * 返回错误响应
     */
    private function errorResponse($message, $code)
    {
        $response = new Response();
        $response->withStatus($code);
        $response->withHeader('Content-Type', 'application/json');
        $response->withBody(json_encode([
            'code' => $code,
            'message' => $message,
            'data' => null,
            'timestamp' => time()
        ]));
        return $response;
    }
}
