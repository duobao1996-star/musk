<?php

namespace app\model;

use think\facade\Db;
use app\model\Right;

class OperationLog
{
    private $rightModel;

    public function __construct()
    {
        $this->rightModel = new Right();
    }

    /**
     * 记录操作日志
     */
    public function log($data)
    {
        return Db::table('pay_operation_log')->insert([
            'admin_id' => $data['admin_id'] ?? null,
            'admin_name' => $data['admin_name'] ?? null,
            'operation_type' => $data['operation_type'],
            'operation_module' => $data['operation_module'] ?? null,
            'operation_desc' => $data['operation_desc'] ?? null,
            'request_method' => $data['request_method'] ?? null,
            'request_url' => $data['request_url'] ?? null,
            'request_params' => $data['request_params'] ?? null,
            'response_code' => $data['response_code'] ?? null,
            'response_msg' => $data['response_msg'] ?? null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'status' => $data['status'] ?? 1,
        ]);
    }

    /**
     * 记录登录日志
     */
    public function logLogin($adminId, $adminName, $status, $ip, $userAgent, $message = '')
    {
        return $this->log([
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'operation_type' => 'login',
            'operation_module' => '认证',
            'operation_desc' => $status ? '登录成功' : '登录失败: ' . $message,
            'request_method' => 'POST',
            'request_url' => '/api/login',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'response_code' => $status ? 200 : 401,
            'response_msg' => $status ? '登录成功' : '登录失败: ' . $message,
            'status' => $status ? 1 : 0
        ]);
    }

    /**
     * 记录登录失败（兼容旧调用名）
     */
    public function logLoginFailure(string $username, string $ip, ?string $userAgent = null, string $message = '用户名或密码错误')
    {
        return $this->log([
            'admin_id' => null,
            'admin_name' => $username ?: '未知用户',
            'operation_type' => 'login',
            'operation_module' => '认证',
            'operation_desc' => '登录失败: ' . $message,
            'request_method' => 'POST',
            'request_url' => '/api/login',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'response_code' => 401,
            'response_msg' => '登录失败: ' . $message,
            'status' => 0
        ]);
    }

    /**
     * 记录登出日志
     */
    public function logLogout($adminId, $adminName, $ip, $userAgent)
    {
        return $this->log([
            'admin_id' => $adminId,
            'admin_name' => $adminName ?: '未知用户',
            'operation_type' => 'logout',
            'operation_module' => '认证',
            'operation_desc' => '用户登出',
            'request_method' => 'POST',
            'request_url' => '/api/logout',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'response_code' => 200,
            'response_msg' => '登出成功',
            'status' => 1
        ]);
    }

    /**
     * 记录操作日志
     */
    public function logOperation($adminId, $adminName, $operationType, $module, $desc, $request, $response = null)
    {
        // 尝试从权限表获取更精确的描述
        $rightDesc = $this->getRightDescription($request['url'] ?? '', $request['method'] ?? 'GET');
        if ($rightDesc) {
            $desc = $rightDesc;
        }

        $normalizedMethod = strtoupper($request['method'] ?? 'GET');
        $normalizedModule = $module ?: 'unknown';
        
        // 修复响应状态码处理
        $code = 200; // 默认成功状态码
        $responseMsg = '';
        $status = 1;
        
        if (is_array($response)) {
            if (isset($response['code'])) {
                $code = (int)$response['code'];
                $status = $code == 200 ? 1 : 0;
            }
            if (isset($response['message'])) {
                $responseMsg = $response['message'];
            }
        }

        // 确保用户名不为空
        $adminName = $adminName ?: '未知用户';

        return $this->log([
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'operation_type' => $operationType,
            'operation_module' => $normalizedModule,
            'operation_desc' => $desc,
            'request_method' => $normalizedMethod,
            'request_url' => $request['url'] ?? null,
            'request_params' => $request['params'] ?? null,
            'response_code' => $code,
            'response_msg' => $responseMsg,
            'ip_address' => $request['ip'] ?? null,
            'user_agent' => $request['user_agent'] ?? null,
            'status' => $status
        ]);
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

    /**
     * 获取操作日志列表
     */
    public function getLogs($page = 1, $limit = 15, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        $query = Db::table('pay_operation_log')->where('is_del', 1);
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }
        if (!empty($filters['admin_name'])) {
            $query->where('admin_name', 'like', '%' . $filters['admin_name'] . '%');
        }
        if (!empty($filters['operation_type'])) {
            $query->where('operation_type', $filters['operation_type']);
        }
        if (!empty($filters['operation_module'])) {
            $query->where('operation_module', $filters['operation_module']);
        }
        if (isset($filters['status'])) {
            $query->where('status', (int)$filters['status']);
        }
        if (!empty($filters['method'])) {
            $query->where('request_method', strtoupper($filters['method']));
        }
        if (!empty($filters['status_code'])) {
            if (is_array($filters['status_code']) && count($filters['status_code']) === 2) {
                $query->whereBetween('response_code', [$filters['status_code'][0], $filters['status_code'][1]]);
            } else {
                $query->where('response_code', (int)$filters['status_code']);
            }
        }
        if (!empty($filters['keyword'])) {
            $kw = '%' . $filters['keyword'] . '%';
            $query->where(function($q) use ($kw) {
                $q->whereLike('operation_desc', $kw)
                  ->whereOr('request_url', 'like', $kw)
                  ->whereOr('admin_name', 'like', $kw);
            });
        }
        if (!empty($filters['start_time'])) {
            $query->where('operation_time', '>=', $filters['start_time']);
        }
        if (!empty($filters['end_time'])) {
            $query->where('operation_time', '<=', $filters['end_time']);
        }
        $total = (clone $query)->count();
        $rows = $query->order('operation_time', 'desc')->limit($offset, $limit)->select();
        $logs = $rows->toArray();
        
        return [
            'data' => $logs,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * 获取操作统计
     */
    public function getStats($filters = [])
    {
        $query = Db::table('pay_operation_log')->where('is_del', 1);
        if (!empty($filters['start_time'])) {
            $query->where('operation_time', '>=', $filters['start_time']);
        }
        if (!empty($filters['end_time'])) {
            $query->where('operation_time', '<=', $filters['end_time']);
        }
        $stats = [];
        $stats['total_operations'] = (clone $query)->count();
        $stats['success_operations'] = (clone $query)->where('status', 1)->count();
        $stats['failed_operations'] = (clone $query)->where('status', 0)->count();
        $stats['type_stats'] = Db::table('pay_operation_log')
            ->fieldRaw('operation_type, COUNT(*) as count')
            ->where('is_del', 1)
            ->group('operation_type')
            ->select()
            ->toArray();
        $stats['module_stats'] = Db::table('pay_operation_log')
            ->fieldRaw('operation_module, COUNT(*) as count')
            ->where('is_del', 1)
            ->group('operation_module')
            ->select()
            ->toArray();
        $stats['today_operations'] = Db::table('pay_operation_log')->whereDay('operation_time', 'today')->count();
        
        return $stats;
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs($days = 30)
    {
        return Db::table('pay_operation_log')
            ->whereRaw('operation_time < DATE_SUB(NOW(), INTERVAL ? DAY)', [$days])
            ->delete() > 0;
    }

    /**
     * 软删除操作日志
     * 
     * @param int $id 日志ID
     * @return bool 删除结果
     */
    public function softDeleteLog(int $id): bool
    {
        return Db::table('pay_operation_log')->where('id', $id)->update([
            'is_del' => 0,
            'delete_time' => Db::raw('NOW()'),
        ]) > 0;
    }

    /**
     * 恢复软删除的操作日志
     * 
     * @param int $id 日志ID
     * @return bool 恢复结果
     */
    public function restoreLog(int $id): bool
    {
        return Db::table('pay_operation_log')->where('id', $id)->update([
            'is_del' => 1,
            'delete_time' => null,
        ]) > 0;
    }

    /**
     * 永久删除操作日志（物理删除）
     * 
     * @param int $id 日志ID
     * @return bool 删除结果
     */
    public function forceDeleteLog(int $id): bool
    {
        return Db::table('pay_operation_log')->where('id', $id)->delete() > 0;
    }

    /**
     * 获取已软删除的日志列表
     * 
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array 日志列表
     */
    public function getDeletedLogs(int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;
        $rows = Db::table('pay_operation_log')
            ->field(['id','admin_id','admin_name','operation_type','operation_time','delete_time'])
            ->where('is_del', 0)
            ->order('delete_time', 'desc')
            ->limit($offset, $limit)
            ->select()
            ->toArray();
        return $rows;
    }

    /**
     * 批量软删除日志
     * 
     * @param array $ids 日志ID数组
     * @return bool 删除结果
     */
    public function batchSoftDeleteLogs(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        return Db::table('pay_operation_log')
            ->whereIn('id', $ids)
            ->update(['is_del' => 0, 'delete_time' => Db::raw('NOW()')]) > 0;
    }

    /**
     * 清理软删除的旧日志（物理删除）
     * 
     * @param int $days 天数
     * @return bool 清理结果
     */
    public function cleanSoftDeletedLogs(int $days = 7): bool
    {
        return Db::table('pay_operation_log')
            ->where('is_del', 0)
            ->whereRaw('delete_time < DATE_SUB(NOW(), INTERVAL ? DAY)', [$days])
            ->delete() > 0;
    }
}
