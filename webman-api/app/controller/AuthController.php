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
 * 
 * 负责处理所有与用户认证相关的功能，包括：
 * - 用户登录验证
 * - JWT令牌生成和刷新
 * - 用户登出
 * - 用户信息获取
 * - 操作日志记录
 * 
 * 注意：此项目仅支持管理员登录，已移除普通用户注册功能
 */
class AuthController extends BaseController
{
    /**
     * JWT密钥
     * 用于签名和验证JWT令牌
     * 
     * @var string
     */
    private string $secretKey;
    
    /**
     * 构造函数
     * 初始化JWT密钥
     */
    public function __construct()
    {
        // 从配置文件中获取JWT密钥
        $this->secretKey = config('jwt.secret');
    }

    /**
     * 用户登录
     * 
     * 处理管理员登录请求，验证用户名和密码，成功后返回JWT令牌
     * 
     * 请求参数：
     * - username: 用户名（必填，3-50字符）
     * - password: 密码（必填，6-100字符）
     * 
     * 成功响应：
     * - token: JWT访问令牌
     * - user: 用户基本信息
     * - expires_in: 令牌过期时间（秒）
     * 
     * @param Request $request 请求对象
     * @return Response 登录响应
     */
    public function login(Request $request): Response
    {
        // 验证登录参数
        $errors = $this->validate($request, [
            'username' => 'required|min:3|max:50',  // 用户名必填，3-50字符
            'password' => 'required|min:6|max:100'  // 密码必填，6-100字符
        ]);

        // 如果参数验证失败，返回错误信息
        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        // 获取请求参数
        $username = $request->post('username');
        $password = $request->post('password');
        $userModel = new User();

        // 仅支持管理员登录，验证用户名和密码
        $user = $userModel->findAdminByCredentials($username, $password);
        $userType = 'admin'; // 固定为管理员类型

        if ($user) {
            // 登录成功，生成JWT令牌
            $expiresIn = config('jwt.expire', 24 * 60 * 60); // 过期时间（秒）
            $payload = [
                'user_id' => $user['id'],           // 用户ID
                'username' => $user['user_name'],   // 用户名
                'email' => $user['email'],          // 邮箱
                'user_type' => $userType,           // 用户类型
                'iat' => time(),                    // 签发时间
                'exp' => time() + $expiresIn        // 过期时间
            ];

            // 使用HS256算法签名JWT令牌
            $token = JWT::encode($payload, $this->secretKey, 'HS256');

            // 保存令牌到数据库
            $userModel->saveUserToken($user['id'], $token, $expiresIn);

            // 记录登录成功日志
            $operationLog = new OperationLog();
            $operationLog->logLogin($user['id'], $user['user_name'], 1, $request->getRealIp(), $request->header('User-Agent'));

            // 返回成功响应
            return $this->success([
                'token' => $token,  // JWT访问令牌
                'user' => [         // 用户基本信息
                    'id' => $user['id'],
                    'username' => $user['user_name'],
                    'email' => $user['email'],
                    'user_type' => $userType,
                    'role_id' => $user['role_id'] ?? null
                ],
                'expires_in' => $expiresIn // 令牌过期时间
            ], '登录成功');
        } else {
            // 登录失败，记录失败日志
            $operationLog = new OperationLog();
            $operationLog->logLoginFailure($username, $request->getRealIp(), $request->header('User-Agent'));

            // 返回错误响应（不区分是用户名错误还是密码错误，提高安全性）
            return $this->error('用户名或密码错误', 401);
        }
    }

    /**
     * 刷新JWT令牌
     * 
     * 当JWT令牌即将过期时，可以使用此接口刷新获取新的令牌
     * 只有在令牌剩余时间少于1小时时才能刷新
     * 
     * 请求头：
     * - Authorization: Bearer <当前令牌>
     * 
     * 成功响应：
     * - token: 新的JWT访问令牌
     * - expires_in: 新令牌过期时间（秒）
     * 
     * @param Request $request 请求对象
     * @return Response 刷新响应
     */
    public function refreshToken(Request $request): Response
    {
        // 获取Authorization请求头
        $authHeader = $request->header('Authorization');
        
        // 验证Authorization头格式是否正确（Bearer token格式）
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('无效的令牌格式', 401);
        }

        // 提取令牌
        $token = $matches[1];

        try {
            // 解码当前令牌（不验证过期时间，因为可能已经过期）
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // 检查令牌是否即将过期（剩余时间少于1小时才允许刷新）
            $remainingTime = $decoded->exp - time();
            if ($remainingTime > 3600) {
                return $this->error('令牌尚未过期，无需刷新', 400);
            }

            // 生成新的令牌，使用原令牌中的用户信息
            $payload = [
                'user_id' => $decoded->user_id,     // 用户ID
                'username' => $decoded->username,   // 用户名
                'email' => $decoded->email,         // 邮箱
                'user_type' => $decoded->user_type, // 用户类型
                'iat' => time(),                    // 新签发时间
                'exp' => time() + config('jwt.expire', 24 * 60 * 60) // 新的过期时间
            ];

            // 使用HS256算法签名新令牌
            $newToken = JWT::encode($payload, $this->secretKey, 'HS256');

            // 更新数据库中的令牌
            $userModel = new User();
            $expiresIn = config('jwt.expire', 24 * 60 * 60);
            $userModel->saveUserToken($decoded->user_id, $newToken, $expiresIn);

            // 返回新令牌
            return $this->success([
                'token' => $newToken,  // 新的JWT访问令牌
                'expires_in' => $expiresIn // 新令牌过期时间
            ], '令牌刷新成功');

        } catch (\Exception $e) {
            // 令牌解码失败（可能是格式错误、签名错误或已过期）
            return $this->error('令牌刷新失败', 401);
        }
    }

    /**
     * 用户注册
     * 
     * 注意：此项目已移除普通用户体系，仅支持管理员登录
     * 此接口已关闭，返回403错误
     * 
     * 请求参数：
     * - username: 用户名（必填，3-50字符）
     * - email: 邮箱（必填，有效邮箱格式）
     * - password: 密码（必填，8-100字符）
     * 
     * @param Request $request 请求对象
     * @return Response 注册响应（始终返回403错误）
     */
    public function register(Request $request): Response
    {
        // 验证注册参数（虽然不会使用，但保持接口完整性）
        $errors = $this->validate($request, [
            'username' => 'required|min:3|max:50',  // 用户名必填，3-50字符
            'email' => 'required|email',            // 邮箱必填，有效邮箱格式
            'password' => 'required|min:8|max:100'  // 密码必填，8-100字符
        ]);

        // 如果参数验证失败，返回错误信息
        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        // 获取请求参数（虽然不会使用）
        $userModel = new User();
        $username = $request->post('username');
        $email = $request->post('email');
        $password = $request->post('password');

        // 检查用户名是否已存在（虽然不会创建用户，但保持逻辑完整性）
        if ($userModel->findAdminByCredentials($username, 'dummy')) {
            return $this->error('用户名已存在', 400);
        }

        // 检查邮箱是否已存在（虽然不会创建用户，但保持逻辑完整性）
        if ($userModel->findAdminByEmail($email, 'dummy')) {
            return $this->error('邮箱已存在', 400);
        }

        // 此项目已移除普通用户体系，不支持注册
        // 返回403禁止访问错误
        return $this->error('注册功能已关闭', 403);
    }

    /**
     * 获取当前用户信息
     * 
     * 获取当前登录用户的详细信息
     * 需要有效的JWT令牌才能访问
     * 
     * 请求头：
     * - Authorization: Bearer <JWT令牌>
     * 
     * 成功响应：
     * - id: 用户ID
     * - username: 用户名
     * - email: 邮箱
     * - user_type: 用户类型
     * - iat: 令牌签发时间
     * - exp: 令牌过期时间
     * 
     * @param Request $request 请求对象
     * @return Response 用户信息响应
     */
    public function me(Request $request): Response
    {
        // 从请求中获取用户信息（由JWT中间件解析后设置）
        $user = $request->user;
        
        // 检查用户是否已登录
        if (!$user) {
            return $this->error('用户未登录', 401);
        }

        // 返回用户信息
        return $this->success([
            'id' => $user->user_id,        // 用户ID
            'username' => $user->username, // 用户名
            'email' => $user->email,       // 邮箱
            'user_type' => $user->user_type, // 用户类型
            'iat' => $user->iat,           // 令牌签发时间
            'exp' => $user->exp            // 令牌过期时间
        ], '获取用户信息成功');
    }

    /**
     * 用户登出
     * 
     * 处理用户登出请求，验证JWT令牌并记录登出日志
     * 只有提供有效令牌的用户才能成功登出
     * 
     * 请求头：
     * - Authorization: Bearer <JWT令牌>
     * 
     * 成功响应：
     * - 返回200状态码和"登出成功"消息
     * 
     * 失败响应：
     * - 401: 缺少认证令牌
     * - 401: 认证令牌无效或已过期
     * 
     * @param Request $request 请求对象
     * @return Response 登出响应
     */
    public function logout(Request $request): Response
    {
        // 从 Authorization 头中获取令牌
        $authHeader = $request->header('Authorization');
        
        // 验证Authorization头是否存在且格式正确
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->error('缺少认证令牌', 401);
        }

        try {
            // 提取并解码JWT令牌
            $token = $matches[1];
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            
            // 验证令牌是否在数据库中有效
            $userModel = new User();
            $user = $userModel->validateToken($token);
            
            if (!$user) {
                return $this->error('令牌已失效或已登出', 401);
            }
            
            // 清除数据库中的令牌
            $userModel->clearToken($token);
            
            // 记录登出日志（包含用户ID、用户名、IP地址和User-Agent）
            $operationLog = new OperationLog();
            $operationLog->logLogout($decoded->user_id, $decoded->username, $request->getRealIp(), $request->header('User-Agent'));
            
            // 返回登出成功响应
            return $this->success(null, '登出成功');
        } catch (\Exception $e) {
            // 令牌解码失败（可能是格式错误、签名错误或已过期）
            return $this->error('认证令牌无效或已过期', 401);
        }
    }
}