<?php

namespace app\controller;

use support\Request;
use support\Response;

/**
 * 基础控制器
 * 提供统一的响应格式和参数验证方法
 */
class BaseController
{
    /**
     * 返回成功响应
     * 
     * @param mixed $data 响应数据
     * @param string $message 响应消息
     * @param int $code 响应状态码
     * @return Response
     */
    protected function success($data = null, string $message = '操作成功', int $code = 200): Response
    {
        // 防止敏感数据泄露
        $safeData = $this->sanitizeResponseData($data);
        
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $safeData,
            'timestamp' => time()
        ]);
    }

    /**
     * 返回错误响应
     * 
     * @param string $message 错误消息
     * @param int $code 错误状态码
     * @param mixed $data 错误数据
     * @return Response
     */
    protected function error(string $message = '操作失败', int $code = 400, $data = null): Response
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }

    /**
     * 返回分页响应
     * 
     * @param mixed $data 分页数据
     * @param int $total 总记录数
     * @param int $page 当前页码
     * @param int $limit 每页记录数
     * @return Response
     */
    protected function paginate($data, int $total, int $page = 1, int $limit = 15, string $message = '获取成功'): Response
    {
        return json([
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ],
            'timestamp' => time()
        ]);
    }

    /**
     * 验证请求参数
     * 
     * @param Request $request 请求对象
     * @param array $rules 验证规则
     * @return array 验证错误信息
     */
    protected function validate(Request $request, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $request->input($field);
            
            // XSS防护：过滤HTML标签和特殊字符
            if (!empty($value)) {
                // 移除HTML标签
                $value = strip_tags($value);
                // HTML实体编码
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                // 移除潜在的SQL注入字符
                $value = str_replace(['\'', '"', ';', '--', '/*', '*/'], '', $value);
            }
            
            // 必填验证
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = $field . ' 不能为空';
                continue;
            }
            
            // 邮箱格式验证
            if (strpos($rule, 'email') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = $field . ' 格式不正确';
            }
            
            // 最小长度验证
            if (strpos($rule, 'min:') !== false && !empty($value)) {
                $min = (int)substr($rule, strpos($rule, 'min:') + 4);
                if (strlen($value) < $min) {
                    $errors[$field] = $field . ' 长度不能少于 ' . $min . ' 个字符';
                }
            }
            
            // 最大长度验证
            if (strpos($rule, 'max:') !== false && !empty($value)) {
                $max = (int)substr($rule, strpos($rule, 'max:') + 4);
                if (strlen($value) > $max) {
                    $errors[$field] = $field . ' 长度不能超过 ' . $max . ' 个字符';
                }
            }
            
            // 数字验证
            if (strpos($rule, 'numeric') !== false && !empty($value) && !is_numeric($value)) {
                $errors[$field] = $field . ' 必须是数字';
            }
            
            // 整数验证
            if (strpos($rule, 'integer') !== false && !empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                $errors[$field] = $field . ' 必须是整数';
            }
            
            // 用户名格式验证（字母数字下划线）
            if (strpos($rule, 'username') !== false && !empty($value) && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $value)) {
                $errors[$field] = $field . ' 只能包含字母、数字、下划线，长度3-50字符';
            }
            
            // 密码强度验证
            if (strpos($rule, 'password') !== false && !empty($value)) {
                if (strlen($value) < 6) {
                    $errors[$field] = $field . ' 长度不能少于6个字符';
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $value)) {
                    $errors[$field] = $field . ' 必须包含大小写字母和数字';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * 安全的输入获取方法
     * 
     * @param Request $request 请求对象
     * @param string $key 参数名
     * @param mixed $default 默认值
     * @return mixed 安全的输入值
     */
    protected function safeInput(Request $request, string $key, $default = null)
    {
        $value = $request->input($key, $default);
        
        if (!empty($value) && is_string($value)) {
            // 移除HTML标签
            $value = strip_tags($value);
            // HTML实体编码
            $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        
        return $value;
    }

    /**
     * 清理响应数据，移除敏感信息
     * 
     * @param mixed $data 原始数据
     * @return mixed 清理后的数据
     */
    protected function sanitizeResponseData($data)
    {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                // 跳过敏感字段（但允许token字段用于登录响应）
                if (in_array(strtolower($key), ['password', 'user_password', 'secret', 'key', 'private_key'])) {
                    continue;
                }
                
                if (is_array($value)) {
                    $sanitized[$key] = $this->sanitizeResponseData($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            return $sanitized;
        }
        
        return $data;
    }

    /**
     * 记录操作日志
     * 
     * @param string $operation 操作类型
     * @param string $module 操作模块
     * @param string $description 操作描述
     * @param array $requestData 请求数据
     * @param int $responseCode 响应状态码
     */
    protected function logOperation(string $operation, string $module, string $description, array $requestData = [], int $responseCode = 200): void
    {
        try {
            $user = request()->user ?? null;
            $adminId = $user->user_id ?? 0;
            $adminName = $user->username ?? '未知用户';
            
            \app\model\OperationLog::create([
                'admin_id' => $adminId,
                'admin_name' => $adminName,
                'operation_type' => $operation,
                'operation_module' => $module,
                'operation_desc' => $description,
                'request_url' => request()->path(),
                'request_method' => request()->method(),
                'request_data' => json_encode($requestData, JSON_UNESCAPED_UNICODE),
                'response_code' => $responseCode,
                'response_msg' => $responseCode >= 200 && $responseCode < 300 ? '成功' : '失败',
                'ip_address' => request()->getRealIp(),
                'user_agent' => request()->header('user-agent', ''),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // 日志记录失败不应该影响主业务
            error_log("操作日志记录失败: " . $e->getMessage());
        }
    }
}
