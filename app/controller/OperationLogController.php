<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\OperationLog;
use app\model\Right;
use app\support\Database;

class OperationLogController extends BaseController
{
    private $logModel;
    private $rightModel;
    private $db;

    public function __construct()
    {
        $this->logModel = new OperationLog();
        $this->rightModel = new Right();
        $this->db = Database::getInstance();
    }

    /**
     * 获取操作日志列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            
            $filters = [
                'admin_id' => $request->get('admin_id'),
                'operation_type' => $request->get('operation_type'),
                'operation_module' => $request->get('operation_module'),
                'status' => $request->get('status'),
                'start_time' => $request->get('start_time'),
                'end_time' => $request->get('end_time')
            ];
            
            // 过滤空值
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $result = $this->logModel->getLogs($page, $limit, $filters);
            
            return $this->paginate($result['data'], $result['total'], $page, $limit);
            
        } catch (\Exception $e) {
            return $this->error('获取操作日志失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取操作统计信息
     */
    public function stats(Request $request): Response
    {
        try {
            $filters = [
                'start_time' => $request->get('start_time'),
                'end_time' => $request->get('end_time')
            ];
            
            // 过滤空值
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $stats = $this->logModel->getStats($filters);
            
            return $this->success($stats, '获取统计信息成功');
            
        } catch (\Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取登录日志
     */
    public function loginLogs(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            
            $filters = [
                'operation_type' => 'login',
                'admin_id' => $request->get('admin_id'),
                'status' => $request->get('status'),
                'start_time' => $request->get('start_time'),
                'end_time' => $request->get('end_time')
            ];
            
            // 过滤空值
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $result = $this->logModel->getLogs($page, $limit, $filters);
            
            return $this->paginate($result['data'], $result['total'], $page, $limit);
            
        } catch (\Exception $e) {
            return $this->error('获取登录日志失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取操作日志详情
     */
    public function show(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('日志ID不能为空', 400);
        }
        
        try {
            $sql = "SELECT * FROM pay_operation_log WHERE id = ?";
            $log = $this->logModel->find($sql, [$id]);
            
            if (!$log) {
                return $this->error('日志不存在', 404);
            }
            
            return $this->success($log, '获取日志详情成功');
            
        } catch (\Exception $e) {
            return $this->error('获取日志详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 清理旧日志
     */
    public function clean(Request $request): Response
    {
        $days = (int)$request->post('days', 30);
        
        if ($days < 7) {
            return $this->error('保留天数不能少于7天', 400);
        }
        
        try {
            $deleted = $this->logModel->cleanOldLogs($days);
            
            return $this->success(['deleted_count' => $deleted], "清理了 {$deleted} 条旧日志");
            
        } catch (\Exception $e) {
            return $this->error('清理日志失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取操作类型列表
     */
    public function operationTypes(Request $request): Response
    {
        try {
            $sql = "SELECT DISTINCT operation_type FROM pay_operation_log ORDER BY operation_type";
            $types = $this->logModel->findAll($sql);
            
            $typeList = array_column($types, 'operation_type');
            
            return $this->success($typeList, '获取操作类型成功');
            
        } catch (\Exception $e) {
            return $this->error('获取操作类型失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取操作模块列表
     */
    public function operationModules(Request $request): Response
    {
        try {
            $sql = "SELECT DISTINCT operation_module FROM pay_operation_log WHERE operation_module IS NOT NULL ORDER BY operation_module";
            $modules = $this->logModel->findAll($sql);
            
            $moduleList = array_column($modules, 'operation_module');
            
            return $this->success($moduleList, '获取操作模块成功');
            
        } catch (\Exception $e) {
            return $this->error('获取操作模块失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取权限列表
     */
    public function rights(Request $request): Response
    {
        try {
            $rights = $this->rightModel->getAllRights();
            
            return $this->success($rights, '获取权限列表成功');
            
        } catch (\Exception $e) {
            return $this->error('获取权限列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 根据URL获取权限描述
     */
    public function getRightByUrl(Request $request): Response
    {
        $url = $request->get('url');
        $method = $request->get('method', 'GET');
        
        if (empty($url)) {
            return $this->error('URL不能为空', 400);
        }
        
        try {
            $right = $this->rightModel->getRightByPath($url, $method);
            
            if ($right) {
                return $this->success($right, '获取权限信息成功');
            } else {
                return $this->success(null, '未找到对应权限');
            }
            
        } catch (\Exception $e) {
            return $this->error('获取权限信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 同步权限描述到操作日志
     */
    public function syncRightDescriptions(Request $request): Response
    {
        try {
            // 获取所有操作日志
            $sql = "SELECT id, request_url, request_method FROM pay_operation_log WHERE operation_desc IS NULL OR operation_desc = ''";
            $logs = $this->db->findAll($sql, []);
            
            $updated = 0;
            foreach ($logs as $log) {
                $right = $this->rightModel->getRightByPath($log['request_url'], $log['request_method']);
                if ($right && $right['description']) {
                    $updateSql = "UPDATE pay_operation_log SET operation_desc = ? WHERE id = ?";
                    $this->db->execute($updateSql, [$right['description'], $log['id']]);
                    $updated++;
                }
            }
            
            return $this->success(['updated_count' => $updated], "同步了 {$updated} 条日志描述");
            
        } catch (\Exception $e) {
            return $this->error('同步权限描述失败: ' . $e->getMessage(), 500);
        }
    }
}
