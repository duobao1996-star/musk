<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\middleware\PerformanceMiddleware;
use app\support\Database;

/**
 * 性能监控控制器
 */
class PerformanceController extends BaseController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取性能状态
     */
    public function status(Request $request): Response
    {
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
        try {
            $threshold = (float)$request->get('threshold', 1000);
            $limit = (int)$request->get('limit', 50);
            
            $sql = "
                SELECT 
                    endpoint,
                    method,
                    response_time,
                    memory_usage,
                    peak_memory,
                    status_code,
                    user_id,
                    ip_address,
                    created_at
                FROM pay_performance_metrics 
                WHERE response_time > ? AND is_del = 1
                ORDER BY response_time DESC
                LIMIT ?
            ";
            
            $slowQueries = $this->db->findAll($sql, [$threshold, $limit]);
            
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
            
            $sql = "
                SELECT 
                    DATE_FORMAT(created_at, ?) as time_period,
                    COUNT(*) as request_count,
                    AVG(response_time) as avg_response_time,
                    MAX(response_time) as max_response_time,
                    AVG(memory_usage) as avg_memory_usage,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count
                FROM pay_performance_metrics 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
                GROUP BY time_period
                ORDER BY time_period ASC
            ";
            
            $trends = $this->db->findAll($sql, [$dateFormat, $days]);
            
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
            
            $sql = "
                SELECT 
                    method,
                    COUNT(*) as request_count,
                    AVG(response_time) as avg_response_time,
                    MIN(response_time) as min_response_time,
                    MAX(response_time) as max_response_time,
                    STDDEV(response_time) as response_time_stddev,
                    AVG(memory_usage) as avg_memory_usage,
                    AVG(peak_memory) as avg_peak_memory,
                    COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
                    ROUND(COUNT(CASE WHEN status_code >= 400 THEN 1 END) * 100.0 / COUNT(*), 2) as error_rate
                FROM pay_performance_metrics 
                WHERE endpoint = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
                GROUP BY method
                ORDER BY request_count DESC
            ";
            
            $details = $this->db->findAll($sql, [$endpoint, $days]);
            
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
        $sql = "
            SELECT 
                COUNT(*) as total_requests,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                AVG(memory_usage) as avg_memory_usage,
                AVG(peak_memory) as avg_peak_memory,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count,
                COUNT(DISTINCT user_id) as active_users,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM pay_performance_metrics 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
        ";
        
        return $this->db->find($sql, [$days]);
    }

    /**
     * 获取端点统计
     */
    private function getEndpointStats(int $days): array
    {
        $sql = "
            SELECT 
                endpoint,
                method,
                COUNT(*) as request_count,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count
            FROM pay_performance_metrics 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
            GROUP BY endpoint, method
            ORDER BY avg_response_time DESC
            LIMIT 20
        ";
        
        return $this->db->findAll($sql, [$days]);
    }

    /**
     * 获取错误统计
     */
    private function getErrorStats(int $days): array
    {
        $sql = "
            SELECT 
                status_code,
                COUNT(*) as error_count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM pay_performance_metrics WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1), 2) as error_percentage
            FROM pay_performance_metrics 
            WHERE status_code >= 400 AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
            GROUP BY status_code
            ORDER BY error_count DESC
        ";
        
        return $this->db->findAll($sql, [$days, $days]);
    }

    /**
     * 获取内存统计
     */
    private function getMemoryStats(int $days): array
    {
        $sql = "
            SELECT 
                AVG(memory_usage) as avg_memory_usage,
                MAX(memory_usage) as max_memory_usage,
                AVG(peak_memory) as avg_peak_memory,
                MAX(peak_memory) as max_peak_memory,
                COUNT(CASE WHEN memory_usage > 10240 THEN 1 END) as high_memory_requests
            FROM pay_performance_metrics 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND is_del = 1
        ";
        
        return $this->db->find($sql, [$days]);
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
}