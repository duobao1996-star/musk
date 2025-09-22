<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\OperationLog;
use app\model\Right;
use think\facade\Db;

class OperationLogController extends BaseController
{
    private $logModel;
    private $rightModel;

    public function __construct()
    {
        $this->logModel = new OperationLog();
        $this->rightModel = new Right();
        
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
            $row = Db::table('pay_operation_log')->where('id', $id)->first();
            $log = $row ? (array)$row : null;
            
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
            $rows = Db::table('pay_operation_log')->select('operation_type')->distinct()->orderBy('operation_type')->get();
            $types = array_map(static fn($r)=>(array)$r, $rows);
            
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
            $rows = Db::table('pay_operation_log')->select('operation_module')->whereNotNull('operation_module')->distinct()->orderBy('operation_module')->get();
            $modules = array_map(static fn($r)=>(array)$r, $rows);
            
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
            $rows = Db::table('pay_operation_log')
                ->field(['id','request_url','request_method'])
                ->whereNull('operation_desc')
                ->select()
                ->toArray();

            $updated = 0;
            foreach ($rows as $log) {
                $right = $this->rightModel->getRightByPath($log['request_url'], $log['request_method'] ?? 'GET');
                if ($right && !empty($right['description'])) {
                    Db::table('pay_operation_log')->where('id', $log['id'])->update(['operation_desc' => $right['description']]);
                    $updated++;
                }
            }

            return $this->success(['updated_count' => $updated], "同步了 {$updated} 条日志描述");

        } catch (\Exception $e) {
            return $this->error('同步权限描述失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 软删除回收站：已删除日志列表
     */
    public function deletedLogs(Request $request): Response
    {
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 15);
        try {
            $data = $this->logModel->getDeletedLogs($page, $limit);
            $total = Db::table('pay_operation_log')->where('is_del', 0)->count();
            return $this->paginate($data, $total, $page, $limit, '获取已删除日志成功');
        } catch (\Exception $e) {
            return $this->error('获取已删除日志失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复已软删除日志
     */
    public function restore(Request $request): Response
    {
        $id = (int)$request->post('id');
        if (!$id) {
            return $this->error('id 必填', 400);
        }
        try {
            $ok = $this->logModel->restoreLog($id);
            return $ok ? $this->success([], '恢复成功') : $this->error('恢复失败', 500);
        } catch (\Exception $e) {
            return $this->error('恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 彻底删除（物理删除）
     */
    public function forceDelete(Request $request): Response
    {
        $id = (int)$request->post('id');
        if (!$id) {
            return $this->error('id 必填', 400);
        }
        try {
            $ok = $this->logModel->forceDeleteLog($id);
            return $ok ? $this->success([], '删除成功') : $this->error('删除失败', 500);
        } catch (\Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 清理已软删除的旧日志（回收站清理）
     */
    public function cleanup(Request $request): Response
    {
        $days = (int)$request->post('days', 7);
        if ($days < 1) {
            $days = 1;
        }
        try {
            $ok = $this->logModel->cleanSoftDeletedLogs($days);
            return $this->success(['cleaned' => $ok ? 1 : 0, 'days' => $days], '回收站清理完成');
        } catch (\Exception $e) {
            return $this->error('回收站清理失败: ' . $e->getMessage(), 500);
        }
    }
}
