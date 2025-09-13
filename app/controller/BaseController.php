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
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
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
    protected function paginate($data, int $total, int $page = 1, int $limit = 15): Response
    {
        return json([
            'code' => 200,
            'message' => '获取成功',
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
            
            // XSS防护：过滤HTML标签
            if (!empty($value)) {
                $value = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            }
            
            // 必填验证
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = $field . ' 不能为空';
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
        }
        
        return $errors;
    }
}
