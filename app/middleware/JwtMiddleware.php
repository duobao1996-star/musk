<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\model\User;

class JwtMiddleware implements MiddlewareInterface
{
    private $secretKey;
    
    public function __construct()
    {
        $this->secretKey = config('jwt.secret');
    }

    public function process(Request $request, callable $handler): Response
    {
        // 跳过不需要认证的路由
        $skipAuth = [
            '/api',
            '/api/',
            '/api/login',
            '/api/register'
        ];

        $path = $request->path();
        if (in_array($path, $skipAuth)) {
            return $handler($request);
        }

        // 获取Authorization头
        $authHeader = $request->header('Authorization');
        
        if (empty($authHeader)) {
            return json([
                'code' => 401,
                'message' => '缺少认证令牌',
                'data' => null
            ], 401);
        }

        // 检查Bearer格式
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return json([
                'code' => 401,
                'message' => '认证令牌格式错误',
                'data' => null
            ], 401);
        }

        $token = $matches[1];

        try {
            // 验证JWT令牌
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // 验证令牌是否在数据库中有效
            $userModel = new User();
            $user = $userModel->validateToken($token);
            
            if (!$user) {
                return json([
                    'code' => 401,
                    'message' => '令牌已失效或已登出',
                    'data' => null
                ], 401);
            }
            
            // 将用户信息添加到请求中
            $request->user = $decoded;
            
            return $handler($request);
        } catch (\Exception $e) {
            return json([
                'code' => 401,
                'message' => '认证令牌无效或已过期',
                'data' => null
            ], 401);
        }
    }
}
