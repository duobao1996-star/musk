<?php

namespace app\controller;

use support\Request;
use support\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use app\model\User;
use app\model\OperationLog;

/**
 * 认证控制器
 * 处理用户登录、注册、注销等认证相关功能
 */
class AuthController extends BaseController
{
    private string $secretKey;
    
    public function __construct()
    {
        $this->secretKey = config('jwt.secret');
    }

    /**
     * 用户登录
     * 
     * @param Request $request 请求对象
     * @return Response 登录响应
     */
    public function login(Request $request): Response
    {
        // 验证登录参数
        $errors = $this->validate($request, [
            'username' => 'required|min:3|max:50',
            'password' => 'required|min:6|max:100'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        $username = $request->post('username');
        $password = $request->post('password');
        $userModel = new User();

        // 优先尝试管理员登录
        $user = $userModel->findAdminByCredentials($username, $password);
        $userType = 'admin';
        
        // 管理员登录失败时尝试普通用户登录
        if (!$user) {
            $user = $userModel->findUserByCredentials($username, $password);
            $userType = 'user';
        }

        if ($user) {
            // 生成JWT令牌
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['user_name'],
                'email' => $user['email'],
                'user_type' => $userType,
                'iat' => time(),
                'exp' => time() + config('jwt.expire', 24 * 60 * 60) // 从配置读取过期时间
            ];

            $token = JWT::encode($payload, $this->secretKey, 'HS256');

            // 记录登录日志
            $operationLog = new OperationLog();
            $operationLog->logLogin($user['id'], $user['user_name'], $request->getRealIp(), $userAgent = $request->header('User-Agent'));

            return $this->success([
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['user_name'],
                    'email' => $user['email'],
                    'user_type' => $userType,
                    'role_id' => $user['role_id'] ?? null
                ],
                'expires_in' => config('jwt.expire', 24 * 60 * 60)
            ], '登录成功');
        } else {
            // 记录登录失败日志
            $operationLog = new OperationLog();
            $operationLog->logLoginFailure($username, $request->getRealIp(), $request->header('User-Agent'));

            return $this->error('用户名或密码错误', 401);
        }
    }

    /**
     * 刷新JWT令牌
     * 
     * @param Request $request 请求对象
     * @return Response 刷新响应
     */
    public function refreshToken(Request $request): Response
    {
        $authHeader = $request->header('Authorization');
        
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('无效的令牌格式', 401);
        }

        $token = $matches[1];

        try {
            // 解码当前令牌（不验证过期时间）
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // 检查令牌是否即将过期（剩余时间少于1小时）
            $remainingTime = $decoded->exp - time();
            if ($remainingTime > 3600) {
                return $this->error('令牌尚未过期，无需刷新', 400);
            }

            // 生成新的令牌
            $payload = [
                'user_id' => $decoded->user_id,
                'username' => $decoded->username,
                'email' => $decoded->email,
                'user_type' => $decoded->user_type,
                'iat' => time(),
                'exp' => time() + config('jwt.expire', 24 * 60 * 60)
            ];

            $newToken = JWT::encode($payload, $this->secretKey, 'HS256');

            return $this->success([
                'token' => $newToken,
                'expires_in' => config('jwt.expire', 24 * 60 * 60)
            ], '令牌刷新成功');

        } catch (\Exception $e) {
            return $this->error('令牌刷新失败', 401);
        }
    }

    /**
     * 用户注册
     * 
     * @param Request $request 请求对象
     * @return Response 注册响应
     */
    public function register(Request $request): Response
    {
        // 验证注册参数
        $errors = $this->validate($request, [
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8|max:100'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        $userModel = new User();
        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');

        // 检查用户名是否已存在
        if ($userModel->findAdminByCredentials($username, 'dummy') || $userModel->findUserByCredentials($username, 'dummy')) {
            return $this->error('用户名已存在', 400);
        }

        // 检查邮箱是否已存在
        if ($userModel->findAdminByEmail($email, 'dummy') || $userModel->findUserByEmail($email, 'dummy')) {
            return $this->error('邮箱已存在', 400);
        }

        // 创建用户
        $userId = $userModel->createUser([
            'user_name' => $username,
            'email' => $email,
            'password' => $password
        ]);

        if ($userId) {
            return $this->success(['user_id' => $userId], '注册成功');
        } else {
            return $this->error('注册失败', 500);
        }
    }

    /**
     * 获取当前用户信息
     * 
     * @param Request $request 请求对象
     * @return Response 用户信息响应
     */
    public function me(Request $request): Response
    {
        $user = $request->user;
        
        if (!$user) {
            return $this->error('用户未登录', 401);
        }

        return $this->success([
            'id' => $user->user_id,
            'username' => $user->username,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'iat' => $user->iat,
            'exp' => $user->exp
        ], '获取用户信息成功');
    }

    /**
     * 用户登出
     * 
     * @param Request $request 请求对象
     * @return Response 登出响应
     */
    public function logout(Request $request): Response
    {
        $user = $request->user;
        
        if ($user) {
            // 记录登出日志
            $operationLog = new OperationLog();
            $operationLog->logLogout($user->user_id, $user->username, $request->getRealIp());
        }

        return $this->success(null, '登出成功');
    }
}