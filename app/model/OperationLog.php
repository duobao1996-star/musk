<?php

namespace app\model;

use app\support\Database;
use app\model\Right;

class OperationLog
{
    private $db;
    private $rightModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rightModel = new Right();
    }

    /**
     * 记录操作日志
     */
    public function log($data)
    {
        $sql = "INSERT INTO pay_operation_log 
                (admin_id, admin_name, operation_type, operation_module, operation_desc, 
                 request_method, request_url, request_params, response_code, response_msg, 
                 ip_address, user_agent, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return $this->db->execute($sql, [
            $data['admin_id'] ?? null,
            $data['admin_name'] ?? null,
            $data['operation_type'],
            $data['operation_module'] ?? null,
            $data['operation_desc'] ?? null,
            $data['request_method'] ?? null,
            $data['request_url'] ?? null,
            $data['request_params'] ?? null,
            $data['response_code'] ?? null,
            $data['response_msg'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['status'] ?? 1
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
            'operation_module' => 'auth',
            'operation_desc' => $status ? '登录成功' : '登录失败: ' . $message,
            'request_method' => 'POST',
            'request_url' => '/api/login',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => $status ? 1 : 0
        ]);
    }

    /**
     * 记录登出日志
     */
    public function logLogout($adminId, $adminName, $ip, $userAgent)
    {
        return $this->log([
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'operation_type' => 'logout',
            'operation_module' => 'auth',
            'operation_desc' => '用户登出',
            'request_method' => 'POST',
            'request_url' => '/api/logout',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
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
        
        return $this->log([
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'operation_type' => $operationType,
            'operation_module' => $module,
            'operation_desc' => $desc,
            'request_method' => $request['method'] ?? null,
            'request_url' => $request['url'] ?? null,
            'request_params' => $request['params'] ?? null,
            'response_code' => $response['code'] ?? null,
            'response_msg' => $response['message'] ?? null,
            'ip_address' => $request['ip'] ?? null,
            'user_agent' => $request['user_agent'] ?? null,
            'status' => $response['code'] == 200 ? 1 : 0
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
        
        // 构建查询条件
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['admin_id'])) {
            $where .= " AND admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        
        if (!empty($filters['operation_type'])) {
            $where .= " AND operation_type = ?";
            $params[] = $filters['operation_type'];
        }
        
        if (!empty($filters['operation_module'])) {
            $where .= " AND operation_module = ?";
            $params[] = $filters['operation_module'];
        }
        
        if (!empty($filters['status'])) {
            $where .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['start_time'])) {
            $where .= " AND operation_time >= ?";
            $params[] = $filters['start_time'];
        }
        
        if (!empty($filters['end_time'])) {
            $where .= " AND operation_time <= ?";
            $params[] = $filters['end_time'];
        }
        
        // 添加软删除条件
        $where .= " AND is_del = 1";
        
        // 查询总数
        $countSql = "SELECT COUNT(*) as total FROM pay_operation_log {$where}";
        $total = $this->db->find($countSql, $params)['total'];
        
        // 查询数据
        $sql = "SELECT * FROM pay_operation_log {$where} ORDER BY operation_time DESC LIMIT {$offset}, {$limit}";
        $logs = $this->db->findAll($sql, $params);
        
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
        $where = "WHERE is_del = 1";
        $params = [];
        
        if (!empty($filters['start_time'])) {
            $where .= " AND operation_time >= ?";
            $params[] = $filters['start_time'];
        }
        
        if (!empty($filters['end_time'])) {
            $where .= " AND operation_time <= ?";
            $params[] = $filters['end_time'];
        }
        
        $stats = [];
        
        // 总操作数
        $total = $this->db->find("SELECT COUNT(*) as total FROM pay_operation_log {$where}", $params)['total'];
        $stats['total_operations'] = $total;
        
        // 成功操作数
        $success = $this->db->find("SELECT COUNT(*) as total FROM pay_operation_log {$where} AND status = 1", $params)['total'];
        $stats['success_operations'] = $success;
        
        // 失败操作数
        $failed = $this->db->find("SELECT COUNT(*) as total FROM pay_operation_log {$where} AND status = 0", $params)['total'];
        $stats['failed_operations'] = $failed;
        
        // 按操作类型统计
        $typeStats = $this->db->findAll("SELECT operation_type, COUNT(*) as count FROM pay_operation_log {$where} GROUP BY operation_type", $params);
        $stats['type_stats'] = $typeStats;
        
        // 按模块统计
        $moduleStats = $this->db->findAll("SELECT operation_module, COUNT(*) as count FROM pay_operation_log {$where} GROUP BY operation_module", $params);
        $stats['module_stats'] = $moduleStats;
        
        // 今日操作数
        $today = $this->db->find("SELECT COUNT(*) as total FROM pay_operation_log WHERE DATE(operation_time) = CURDATE()")['total'];
        $stats['today_operations'] = $today;
        
        return $stats;
    }

    /**
     * 清理旧日志
     */
    public function cleanOldLogs($days = 30)
    {
        $sql = "DELETE FROM pay_operation_log WHERE operation_time < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$days]);
    }

    /**
     * 软删除操作日志
     * 
     * @param int $id 日志ID
     * @return bool 删除结果
     */
    public function softDeleteLog(int $id): bool
    {
        $sql = "UPDATE pay_operation_log SET is_del = 0, delete_time = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 恢复软删除的操作日志
     * 
     * @param int $id 日志ID
     * @return bool 恢复结果
     */
    public function restoreLog(int $id): bool
    {
        $sql = "UPDATE pay_operation_log SET is_del = 1, delete_time = NULL WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 永久删除操作日志（物理删除）
     * 
     * @param int $id 日志ID
     * @return bool 删除结果
     */
    public function forceDeleteLog(int $id): bool
    {
        $sql = "DELETE FROM pay_operation_log WHERE id = ?";
        return $this->db->execute($sql, [$id]);
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
        $sql = "SELECT id, admin_id, admin_name, operation_type, operation_time, delete_time FROM pay_operation_log WHERE is_del = 0 ORDER BY delete_time DESC LIMIT {$offset}, {$limit}";
        return $this->db->findAll($sql);
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

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE pay_operation_log SET is_del = 0, delete_time = NOW() WHERE id IN ({$placeholders})";
        return $this->db->execute($sql, $ids);
    }

    /**
     * 清理软删除的旧日志（物理删除）
     * 
     * @param int $days 天数
     * @return bool 清理结果
     */
    public function cleanSoftDeletedLogs(int $days = 7): bool
    {
        $sql = "DELETE FROM pay_operation_log WHERE is_del = 0 AND delete_time < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$days]);
    }
}
