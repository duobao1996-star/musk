<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\middleware\PerformanceMiddleware;
use think\facade\Db;

/**
 * 性能监控控制器
 */
class PerformanceController extends BaseController
{
    public function __construct()
    {
    }

    /**
     * 获取性能状态
     */
    public function status(Request $request): Response
    {
        // 检查用户权限
        $user = $request->user ?? null;
        if (!$user) {
            return $this->error('用户未登录', 401);
        }
        
        // 检查是否有性能监控查看权限
        if (!$this->hasPerformanceViewPermission($user->user_id)) {
            return $this->error('权限不足', 403);
        }
        
        try {
            $days = (int)$request->get('days', 7);
            
            // 获取基本统计
            $basicStats = $this->getBasicStats($days);
            
            // 获取端点性能统计
            $endpointStats = $this->getEndpointStats($days);
            
            // 获取错误统计
            $errorStats = $this->getErrorStats($days);
            
            // 获取内存使用统计
            $memoryStats = $this->getMemoryStats($days);
            
            return $this->success([
                'basic_stats' => $basicStats,
                'endpoint_stats' => $endpointStats,
                'error_stats' => $errorStats,
                'memory_stats' => $memoryStats,
                'period' => "最近{$days}天"
            ], '获取性能状态成功');
            
        } catch (\Exception $e) {
            return $this->error('获取性能状态失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 清除缓存
     */
    public function clearCache(Request $request): Response
    {
        try {
            $cacheType = $request->post('type', 'all');
            $cleared = 0;
            
            if ($cacheType === 'all' || $cacheType === 'redis') {
                $redis = \app\support\Cache::getInstance();
                $redis->flush();
                $cleared++;
            }
            
            if ($cacheType === 'all' || $cacheType === 'file') {
                $this->clearFileCache();
                $cleared++;
            }
            
            return $this->success([
                'cleared_types' => $cleared,
                'cache_type' => $cacheType
            ], '缓存清除成功');
            
        } catch (\Exception $e) {
            return $this->error('缓存清除失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取慢查询
     */
    public function slowQueries(Request $request): Response
    {
        // 检查用户权限
        $user = $request->user ?? null;
        if (!$user) {
            return $this->error('用户未登录', 401);
        }
        
        // 检查是否有性能监控查看权限
        if (!$this->hasPerformanceViewPermission($user->user_id)) {
            return $this->error('权限不足', 403);
        }
        
        try {
            $threshold = (float)$request->get('threshold', 1000);
            $limit = (int)$request->get('limit', 50);
            
            $rows = Db::table('pay_performance_metrics')
                ->field(['endpoint','method','response_time','memory_usage','peak_memory','status_code','user_id','ip_address','created_at'])
                ->where('response_time','>', $threshold)
                ->where('is_del', 1)
                ->order('response_time','desc')
                ->limit($limit)
                ->select();
            $slowQueries = $rows->toArray();
            
            return $this->success([
                'slow_queries' => $slowQueries,
                'threshold' => $threshold,
                'count' => count($slowQueries)
            ], '获取慢查询成功');
            
        } catch (\Exception $e) {
            return $this->error('获取慢查询失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取性能趋势
     */
    public function trends(Request $request): Response
    {
        try {
            $days = (int)$request->get('days', 7);
            $interval = $request->get('interval', 'hour'); // hour, day
            
            $dateFormat = $interval === 'hour' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
            
            $rows = Db::table('pay_performance_metrics')
                ->fieldRaw("DATE_FORMAT(created_at, '{$dateFormat}') as time_period, COUNT(*) as request_count, AVG(response_time) as avg_response_time, MAX(response_time) as max_response_time, AVG(memory_usage) as avg_memory_usage, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count")
                ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
                ->where('is_del', 1)
                ->group('time_period')
                ->order('time_period','asc')
                ->select();
            $trends = $rows->toArray();
            
            return $this->success([
                'trends' => $trends,
                'period' => "最近{$days}天",
                'interval' => $interval
            ], '获取性能趋势成功');
            
        } catch (\Exception $e) {
            return $this->error('获取性能趋势失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取端点性能详情
     */
    public function endpointDetails(Request $request, string $endpoint): Response
    {
        try {
            $days = (int)$request->get('days', 7);
            
            // 统计总量用于计算错误率
            $total = Db::table('pay_performance_metrics')
                ->where('endpoint', $endpoint)
                ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
                ->where('is_del', 1)
                ->count();

            $rows = Db::table('pay_performance_metrics')
                ->fieldRaw('method, COUNT(*) as request_count, AVG(response_time) as avg_response_time, MIN(response_time) as min_response_time, MAX(response_time) as max_response_time, AVG(memory_usage) as avg_memory_usage, AVG(peak_memory) as avg_peak_memory, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count')
                ->where('endpoint', $endpoint)
                ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
                ->where('is_del', 1)
                ->group('method')
                ->order('request_count','desc')
                ->select();
            $arr = $rows->toArray();
            foreach ($arr as &$r) {
                $r['error_rate'] = $total ? round(($r['error_count'] * 100.0) / $total, 2) : 0;
            }
            $details = $arr;
            
            return $this->success([
                'endpoint' => $endpoint,
                'details' => $details,
                'period' => "最近{$days}天"
            ], '获取端点性能详情成功');
            
        } catch (\Exception $e) {
            return $this->error('获取端点性能详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取基本统计
     */
    private function getBasicStats(int $days): array
    {
        $row = Db::table('pay_performance_metrics')
            ->fieldRaw('COUNT(*) as total_requests, AVG(response_time) as avg_response_time, MAX(response_time) as max_response_time, AVG(memory_usage) as avg_memory_usage, AVG(peak_memory) as avg_peak_memory, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count, COUNT(DISTINCT user_id) as active_users, COUNT(DISTINCT ip_address) as unique_ips')
            ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
            ->where('is_del', 1)
            ->find();
        return $row ? (array)$row : [];
    }

    /**
     * 获取端点统计
     */
    private function getEndpointStats(int $days): array
    {
        $rows = Db::table('pay_performance_metrics')
            ->fieldRaw('endpoint, method, COUNT(*) as request_count, AVG(response_time) as avg_response_time, MAX(response_time) as max_response_time, SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count')
            ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
            ->where('is_del', 1)
            ->group('endpoint,method')
            ->order('avg_response_time','desc')
            ->limit(20)
            ->select();
        return $rows->toArray();
    }

    /**
     * 获取错误统计
     */
    private function getErrorStats(int $days): array
    {
        $total = Db::table('pay_performance_metrics')
            ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
            ->where('is_del', 1)
            ->count();
        $rows = Db::table('pay_performance_metrics')
            ->fieldRaw('status_code, COUNT(*) as error_count')
            ->where('status_code', '>=', 400)
            ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
            ->where('is_del', 1)
            ->group('status_code')
            ->order('error_count','desc')
            ->select();
        $arr = $rows->toArray();
        foreach ($arr as &$r) {
            $r['error_percentage'] = $total ? round(($r['error_count'] * 100.0) / $total, 2) : 0;
        }
        return $arr;
    }

    /**
     * 获取内存统计
     */
    private function getMemoryStats(int $days): array
    {
        $row = Db::table('pay_performance_metrics')
            ->fieldRaw('AVG(memory_usage) as avg_memory_usage, MAX(memory_usage) as max_memory_usage, AVG(peak_memory) as avg_peak_memory, MAX(peak_memory) as max_peak_memory, SUM(CASE WHEN memory_usage > 10240 THEN 1 ELSE 0 END) as high_memory_requests')
            ->whereRaw("created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")
            ->where('is_del', 1)
            ->find();
        return $row ? (array)$row : [];
    }

    /**
     * 清除文件缓存
     */
    private function clearFileCache(): void
    {
        $cacheDir = runtime_path() . '/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * 检查用户是否有性能监控查看权限
     */
    private function hasPerformanceViewPermission($userId): bool
    {
        try {
            // 检查是否为超级管理员
            $isSuperAdmin = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role' => 'r'], 'ar.role_id = r.id')
                ->where('ar.admin_id', $userId)
                ->where('r.role_name', '超级管理员')
                ->count() > 0;
                
            if ($isSuperAdmin) {
                return true;
            }
            
            // 检查是否有性能监控查看权限 (ID: 22)
            $hasPermission = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role_right' => 'rr'], 'ar.role_id = rr.role_id')
                ->where('ar.admin_id', $userId)
                ->where('rr.right_id', 22) // 性能监控权限
                ->count() > 0;
                
            return $hasPermission;
        } catch (\Exception $e) {
            error_log("检查性能监控权限失败: " . $e->getMessage());
            return false;
        }
    }
}