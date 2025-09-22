<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\support\SecurityHelper;

/**
 * CSRF防护中间件
 * 保护POST、PUT、DELETE请求免受CSRF攻击
 */
class CsrfMiddleware implements MiddlewareInterface
{
    private array $excludedPaths = [
        '/api/login',
        '/api/register',
        '/api/refresh-token',
        '/api/logout',
        '/api/roles',
        '/api/roles/',
        '/api/rights',
        '/api/rights/',
        '/api/admins',
        '/api/admins/',
        '/api/operation-logs',
        '/api/operation-logs/',
        '/api/performance',
        '/api/performance/'
    ];

    /**
     * 检查路径是否应该排除CSRF验证
     */
    private function shouldExcludePath($path): bool
    {
        // 所有 API 路径都排除 CSRF 保护
        if (strpos($path, '/api/') === 0) {
            return true;
        }
        
        // 静态资源排除
        if (strpos($path, '/static/') === 0) {
            return true;
        }
        
        // 文档页面排除
        if (strpos($path, '/api-docs') === 0) {
            return true;
        }
        
        return false;
    }

    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        $method = $request->method();

        // 跳过不需要CSRF保护的路径
        if ($this->shouldExcludePath($path)) {
            return $handler($request);
        }

        // 只对修改数据的请求进行CSRF检查
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = $request->header('X-CSRF-Token') ?: $request->input('_token');
            
            if (!$this->validateCsrfToken($token, $request)) {
                return json([
                    'code' => 403,
                    'message' => 'CSRF令牌验证失败',
                    'data' => null
                ], 403);
            }
        }

        // 为GET请求生成CSRF令牌
        if ($method === 'GET') {
            $response = $handler($request);
            $csrfToken = $this->generateCsrfToken($request);
            
            return $response->withHeaders([
                'X-CSRF-Token' => $csrfToken
            ]);
        }

        return $handler($request);
    }

    /**
     * 验证CSRF令牌
     * 
     * @param string|null $token 客户端提供的令牌
     * @param Request $request 请求对象
     * @return bool 验证结果
     */
    private function validateCsrfToken(?string $token, Request $request): bool
    {
        if (empty($token)) {
            return false;
        }

        // 从会话中获取存储的令牌
        $sessionToken = $request->session('csrf_token');
        
        if (empty($sessionToken)) {
            return false;
        }

        // 使用安全比较防止时序攻击
        return SecurityHelper::secureCompare($token, $sessionToken);
    }

    /**
     * 生成CSRF令牌
     * 
     * @param Request $request 请求对象
     * @return string CSRF令牌
     */
    private function generateCsrfToken(Request $request): string
    {
        $token = SecurityHelper::generateCsrfToken();
        $request->session()->set('csrf_token', $token);
        return $token;
    }
}
