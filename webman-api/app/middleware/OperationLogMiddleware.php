<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\model\OperationLog;
use app\model\Right;

class OperationLogMiddleware implements MiddlewareInterface
{
    private $rightModel;

    public function __construct()
    {
        $this->rightModel = new Right();
    }

    public function process(Request $request, callable $handler): Response
    {
        $startTime = microtime(true);
        
        // 处理请求
        $response = $handler($request);
        
        // 记录操作日志
        $this->logOperation($request, $response, $startTime);
        
        return $response;
    }

    private function logOperation(Request $request, Response $response, $startTime)
    {
        try {
            // 跳过静态资源/文档等
            $path = $request->path();
            if (str_starts_with($path, '/static/') || str_starts_with($path, '/favicon') || str_starts_with($path, '/api-docs')) {
                return;
            }

            // 日志白名单（完全不记录）
            $whitelist = [
                '/api/roles',
                '/api/permissions',
                '/api/operation-logs',
                '/api/performance',
                // 控制器内已显式记录，避免重复
                '/api/admins',
                '/api/login',
                '/api/logout',
            ];
            foreach ($whitelist as $w) {
                if ($path === $w || str_starts_with($path, $w . '/')) {
                    return;
                }
            }
            
            // 获取用户信息
            $user = $request->user ?? null;
            $adminId = $user->user_id ?? null;
            $adminName = $user->username ?? '未知用户';
            
            // 获取请求信息
            $method = $request->method();
            $url = $request->url();
            $ip = $this->getClientIp($request);
            $userAgent = $request->header('User-Agent', '');
            
            // 获取请求参数（排除敏感信息）
            $params = $this->getRequestParams($request);
            
            // 获取响应信息
            $responseData = json_decode($response->rawBody(), true);
            $responseCode = isset($responseData['code']) ? (int)$responseData['code'] : (int)$response->getStatusCode();
            $responseMsg = $responseData['message'] ?? '';

            // 仅记录会改数据的请求，或错误/敏感GET
            $mutatingMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
            $forceLogPaths = ['/api/login', '/api/logout', '/api/me'];
            $isForcePath = false;
            foreach ($forceLogPaths as $fp) {
                if ($path === $fp || str_starts_with($path, $fp . '/')) {
                    $isForcePath = true; break;
                }
            }
            if (!in_array($method, $mutatingMethods, true)) {
                // 对GET：仅当错误或敏感路径时记录
                if ($responseCode < 400 && !$isForcePath) {
                    return;
                }
            }
            
            // 确定操作类型和模块
            $operationInfo = $this->getOperationInfo($path, $method);
            
            // 尝试从权限表获取更精确的描述
            $rightDesc = $this->getRightDescription($url, $method);
            if ($rightDesc) {
                $operationInfo['desc'] = $rightDesc;
            }
            // 若仍为空，基于路径+方法猜测中文描述
            if (empty($operationInfo['desc'])) {
                $operationInfo['desc'] = $this->guessDesc($method, $path);
            }
            
            // 记录日志
            $logModel = new OperationLog();
            $logModel->logOperation($adminId, $adminName, $operationInfo['type'], $operationInfo['module'], $operationInfo['desc'], [
                'method' => $method,
                'url' => $url,
                'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
                'ip' => $ip,
                'user_agent' => $userAgent
            ], [
                'code' => $responseCode,
                'message' => $responseMsg
            ]);
            
        } catch (\Exception $e) {
            // 记录日志失败不影响主流程
            error_log('Operation log error: ' . $e->getMessage());
        }
    }

    private function getClientIp(Request $request)
    {
        $ip = $request->header('X-Forwarded-For');
        if ($ip) {
            $ips = explode(',', $ip);
            return trim($ips[0]);
        }
        
        $ip = $request->header('X-Real-IP');
        if ($ip) {
            return $ip;
        }
        
        return $request->getRemoteIp();
    }

    private function getRequestParams(Request $request)
    {
        $params = [];
        
        // GET参数
        $getParams = $request->get();
        if (!empty($getParams)) {
            $params['GET'] = $getParams;
        }
        
        // POST参数
        $postParams = $request->post();
        if (!empty($postParams)) {
            // 过滤敏感信息
            $filteredParams = $this->filterSensitiveData($postParams);
            $params['POST'] = $filteredParams;
        }
        
        return $params;
    }

    private function filterSensitiveData($params)
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'pwd'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($params[$field])) {
                $params[$field] = '***';
            }
        }
        
        return $params;
    }

    private function getOperationInfo($path, $method)
    {
        $info = [
            'type' => 'unknown',
            'module' => 'unknown',
            'desc' => ''
        ];
        
        // 解析路径
        $pathParts = explode('/', trim($path, '/'));
        
        if (count($pathParts) >= 2) {
            $module = $pathParts[1]; // 第1段通常为 api
            $second = $pathParts[2] ?? '';
            
            switch ($second) {
                case 'admin':
                    $info['module'] = '管理员';
                    $info['desc'] = $this->getAdminOperationDesc($path, $method);
                    break;
                case 'admins':
                    // 统一管理员模块（复数）
                    $info['module'] = '管理员';
                    $info['desc'] = $this->getAdminOperationDesc($path, $method);
                    break;
                case 'merchant':
                    $info['module'] = '商户管理';
                    $info['desc'] = $this->getMerchantOperationDesc($path, $method);
                    break;
                case 'user':
                    $info['module'] = '用户管理';
                    $info['desc'] = $this->getUserOperationDesc($path, $method);
                    break;
                case 'permissions':
                    $info['module'] = '权限管理';
                    break;
                case 'roles':
                    $info['module'] = '角色管理';
                    break;
                case 'operation-logs':
                    $info['module'] = '操作日志';
                    break;
                case 'performance':
                    $info['module'] = '性能监控';
                    break;
                case 'login':
                case 'logout':
                case 'refresh-token':
                case 'me':
                    $info['module'] = '认证';
                    break;
                default:
                    // 回退到第二段作为模块名，并翻译为中文
                    if (!empty($second)) {
                        $info['module'] = $this->translateModuleName($second);
                    }
            }
        }
        
        // 确定操作类型（部分路径优先判断）
        if (strpos($path, '/login') !== false) {
            $info['type'] = 'login';
        } elseif (strpos($path, '/logout') !== false) {
            $info['type'] = 'logout';
        } elseif (preg_match('#/admins/\d+/(reset-password|toggle-status)#', $path)) {
            // 这些操作语义为更新
            $info['type'] = 'update';
        } elseif ($method === 'GET') {
            $info['type'] = 'view';
        } elseif ($method === 'POST') {
            $info['type'] = 'create';
        } elseif ($method === 'PUT') {
            $info['type'] = 'update';
        } elseif ($method === 'DELETE') {
            $info['type'] = 'delete';
        }
        
        return $info;
    }

    /**
     * 翻译模块名称为中文
     */
    private function translateModuleName($moduleName)
    {
        $translations = [
            'admin' => '管理员',
            'admins' => '管理员',
            'auth' => '认证',
            'permission' => '权限管理',
            'role' => '角色管理',
            'operation_log' => '操作日志',
            'performance' => '性能监控',
            'system' => '系统管理',
            'user' => '用户管理',
            'merchant' => '商户管理',
            'unknown' => '未知'
        ];
        
        return $translations[$moduleName] ?? $moduleName;
    }

    private function guessDesc(string $method, string $path): string
    {
        $m = strtoupper($method);
        $action = [
            'GET' => '查看',
            'POST' => '创建',
            'PUT' => '更新',
            'DELETE' => '删除'
        ][$m] ?? $m;
        if (str_starts_with($path, '/api/permissions/menu')) return '获取菜单权限';
        if (str_starts_with($path, '/api/permissions/tree')) return '查看权限树';
        if (str_starts_with($path, '/api/permissions')) return $action . '权限';
        if (str_starts_with($path, '/api/roles/all-rights-tree')) return '查看权限树';
        if (str_starts_with($path, '/api/roles')) return $action . '角色';
        // 管理员账号
        if (preg_match('#^/api/admins/\d+/reset-password#', $path)) return '重置管理员密码';
        if (preg_match('#^/api/admins/\d+/toggle-status#', $path)) return '切换管理员状态';
        if (preg_match('#^/api/admins/\d+$#', $path) && $m === 'DELETE') return '删除管理员';
        if (str_starts_with($path, '/api/admins')) return $action . '管理员';
        if (str_starts_with($path, '/api/operation-logs/stats')) return '查看操作统计';
        if (str_starts_with($path, '/api/operation-logs/clean')) return '清理旧日志';
        if (str_starts_with($path, '/api/operation-logs')) return $action . '操作日志';
        if (str_starts_with($path, '/api/login')) return '用户登录';
        if (str_starts_with($path, '/api/logout')) return '用户登出';
        if (str_starts_with($path, '/api/refresh-token')) return '刷新令牌';
        if (str_starts_with($path, '/api/me')) return '获取我的信息';
        return $action . ' ' . $path;
    }

    /**
     * 根据URL和方法获取权限描述
     */
    private function getRightDescription($url, $method)
    {
        try {
            $right = $this->rightModel->getRightByPath($url, $method);
            return $right ? $right['description'] : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getAdminOperationDesc($path, $method)
    {
        if (strpos($path, '/admin/stats') !== false) {
            return '查看管理员统计信息';
        } elseif (strpos($path, '/admin/') !== false) {
            if ($method === 'GET') {
                return '查看管理员列表';
            } elseif ($method === 'POST') {
                return '创建管理员';
            } elseif ($method === 'PUT') {
                return '更新管理员信息';
            } elseif ($method === 'DELETE') {
                return '删除管理员';
            }
        }
        return '管理员操作';
    }

    private function getMerchantOperationDesc($path, $method)
    {
        if (strpos($path, '/merchant/stats') !== false) {
            return '查看商户统计信息';
        } elseif (strpos($path, '/merchant/reset-password') !== false) {
            return '重置商户密码';
        } elseif (strpos($path, '/merchant/toggle-status') !== false) {
            return '切换商户状态';
        } elseif (strpos($path, '/merchant/') !== false) {
            if ($method === 'GET') {
                return '查看商户列表';
            } elseif ($method === 'POST') {
                return '创建商户';
            } elseif ($method === 'PUT') {
                return '更新商户信息';
            } elseif ($method === 'DELETE') {
                return '删除商户';
            }
        }
        return '商户操作';
    }

    private function getUserOperationDesc($path, $method)
    {
        if (strpos($path, '/users') !== false) {
            if ($method === 'GET') {
                return '查看用户列表';
            }
        } elseif (strpos($path, '/user/') !== false) {
            if ($method === 'GET') {
                return '查看用户详情';
            } elseif ($method === 'POST') {
                return '创建用户';
            } elseif ($method === 'PUT') {
                return '更新用户信息';
            } elseif ($method === 'DELETE') {
                return '删除用户';
            }
        }
        return '用户操作';
    }
}
