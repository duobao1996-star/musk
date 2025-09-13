<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\User;
use app\model\Right;
use app\model\OperationLog;
use app\middleware\PerformanceMiddleware;

/**
 * 软删除管理控制器
 * 提供软删除数据的恢复、永久删除等功能
 */
class SoftDeleteController extends BaseController
{
    private User $userModel;
    private Right $rightModel;
    private OperationLog $logModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->rightModel = new Right();
        $this->logModel = new OperationLog();
    }

    /**
     * 获取已软删除的管理员列表
     */
    public function getDeletedAdmins(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $limit = (int)($request->get('limit', 15));
            
            $admins = $this->userModel->getDeletedAdmins($page, $limit);
            
            return $this->success([
                'data' => $admins,
                'page' => $page,
                'limit' => $limit
            ], '获取已删除管理员列表成功');
            
        } catch (\Exception $e) {
            return $this->error('获取已删除管理员列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取已软删除的普通用户列表
     */
    public function getDeletedUsers(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $limit = (int)($request->get('limit', 15));
            
            $users = $this->userModel->getDeletedUsers($page, $limit);
            
            return $this->success([
                'data' => $users,
                'page' => $page,
                'limit' => $limit
            ], '获取已删除用户列表成功');
            
        } catch (\Exception $e) {
            return $this->error('获取已删除用户列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取已软删除的权限列表
     */
    public function getDeletedRights(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $limit = (int)($request->get('limit', 15));
            
            $rights = $this->rightModel->getDeletedRights($page, $limit);
            
            return $this->success([
                'data' => $rights,
                'page' => $page,
                'limit' => $limit
            ], '获取已删除权限列表成功');
            
        } catch (\Exception $e) {
            return $this->error('获取已删除权限列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取已软删除的操作日志列表
     */
    public function getDeletedLogs(Request $request): Response
    {
        try {
            $page = (int)($request->get('page', 1));
            $limit = (int)($request->get('limit', 15));
            
            $logs = $this->logModel->getDeletedLogs($page, $limit);
            
            return $this->success([
                'data' => $logs,
                'page' => $page,
                'limit' => $limit
            ], '获取已删除日志列表成功');
            
        } catch (\Exception $e) {
            return $this->error('获取已删除日志列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复管理员
     */
    public function restoreAdmin(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('管理员ID不能为空', 400);
            }
            
            $result = $this->userModel->restoreAdmin($id);
            
            if ($result) {
                return $this->success(null, '管理员恢复成功');
            } else {
                return $this->error('管理员恢复失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('管理员恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复普通用户
     */
    public function restoreUser(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('用户ID不能为空', 400);
            }
            
            $result = $this->userModel->restoreUser($id);
            
            if ($result) {
                return $this->success(null, '用户恢复成功');
            } else {
                return $this->error('用户恢复失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('用户恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复权限
     */
    public function restoreRight(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('权限ID不能为空', 400);
            }
            
            $result = $this->rightModel->restoreRight($id);
            
            if ($result) {
                return $this->success(null, '权限恢复成功');
            } else {
                return $this->error('权限恢复失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('权限恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 恢复操作日志
     */
    public function restoreLog(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('日志ID不能为空', 400);
            }
            
            $result = $this->logModel->restoreLog($id);
            
            if ($result) {
                return $this->success(null, '日志恢复成功');
            } else {
                return $this->error('日志恢复失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('日志恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 永久删除管理员
     */
    public function forceDeleteAdmin(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('管理员ID不能为空', 400);
            }
            
            $result = $this->userModel->forceDeleteAdmin($id);
            
            if ($result) {
                return $this->success(null, '管理员永久删除成功');
            } else {
                return $this->error('管理员永久删除失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('管理员永久删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 永久删除普通用户
     */
    public function forceDeleteUser(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('用户ID不能为空', 400);
            }
            
            $result = $this->userModel->forceDeleteUser($id);
            
            if ($result) {
                return $this->success(null, '用户永久删除成功');
            } else {
                return $this->error('用户永久删除失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('用户永久删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 永久删除权限
     */
    public function forceDeleteRight(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('权限ID不能为空', 400);
            }
            
            $result = $this->rightModel->forceDeleteRight($id);
            
            if ($result) {
                return $this->success(null, '权限永久删除成功');
            } else {
                return $this->error('权限永久删除失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('权限永久删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 永久删除操作日志
     */
    public function forceDeleteLog(Request $request): Response
    {
        try {
            $id = (int)$request->post('id');
            
            if (!$id) {
                return $this->error('日志ID不能为空', 400);
            }
            
            $result = $this->logModel->forceDeleteLog($id);
            
            if ($result) {
                return $this->success(null, '日志永久删除成功');
            } else {
                return $this->error('日志永久删除失败', 500);
            }
            
        } catch (\Exception $e) {
            return $this->error('日志永久删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量恢复操作日志
     */
    public function batchRestoreLogs(Request $request): Response
    {
        try {
            $ids = $request->post('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return $this->error('日志ID列表不能为空', 400);
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $result = $this->logModel->restoreLog((int)$id);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            return $this->success([
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total' => count($ids)
            ], "批量恢复完成：成功 {$successCount} 个，失败 {$failCount} 个");
            
        } catch (\Exception $e) {
            return $this->error('批量恢复失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量永久删除操作日志
     */
    public function batchForceDeleteLogs(Request $request): Response
    {
        try {
            $ids = $request->post('ids', []);
            
            if (empty($ids) || !is_array($ids)) {
                return $this->error('日志ID列表不能为空', 400);
            }
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($ids as $id) {
                $result = $this->logModel->forceDeleteLog((int)$id);
                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
            
            return $this->success([
                'success_count' => $successCount,
                'fail_count' => $failCount,
                'total' => count($ids)
            ], "批量永久删除完成：成功 {$successCount} 个，失败 {$failCount} 个");
            
        } catch (\Exception $e) {
            return $this->error('批量永久删除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 清理软删除的旧数据
     */
    public function cleanupSoftDeletedData(Request $request): Response
    {
        try {
            $days = (int)($request->post('days', 7));
            
            if ($days < 1) {
                return $this->error('清理天数必须大于0', 400);
            }
            
            // 清理软删除的操作日志
            $logResult = $this->logModel->cleanSoftDeletedLogs($days);
            
            // 清理软删除的性能数据
            $performanceResult = PerformanceMiddleware::cleanSoftDeletedMetrics($days);
            
            return $this->success([
                'log_cleaned' => $logResult,
                'performance_cleaned' => $performanceResult,
                'days' => $days
            ], "清理完成：清理了 {$days} 天前的软删除数据");
            
        } catch (\Exception $e) {
            return $this->error('清理软删除数据失败: ' . $e->getMessage(), 500);
        }
    }
}
