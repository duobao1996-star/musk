<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\support\Database;

/**
 * 性能监控中间件 - PHP 8.2 优化版本
 */
class PerformanceMiddleware implements MiddlewareInterface
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function process(Request $request, callable $handler): Response
    {
        // 使用PHP 8.2的高精度时间
        $startTime = hrtime(true);
        $startMemory = memory_get_usage();
        
        // 记录请求信息
        $requestData = [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'request_size' => strlen($request->rawBody()),
            'user_id' => $request->user->id ?? null,
            'ip_address' => $request->getRealIp(),
            'user_agent' => $request->header('User-Agent'),
        ];

        $response = $handler($request);

        // 计算性能指标
        $endTime = hrtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = ($endTime - $startTime) / 1_000_000; // 转换为毫秒
        $memoryUsage = ($endMemory - $startMemory) / 1024; // KB
        $peakMemory = memory_get_peak_usage() / 1024 / 1024; // MB
        
        // 获取响应信息
        $responseData = $response->rawBody();
        $responseSize = strlen($responseData);
        $statusCode = $response->getStatusCode();

        // 添加性能头部
        $response = $response->withHeaders([
            'X-Response-Time' => round($executionTime, 2) . 'ms',
            'X-Memory-Usage' => round($memoryUsage, 2) . 'KB',
            'X-Peak-Memory' => round($peakMemory, 2) . 'MB',
            'X-DB-Queries' => $this->getQueryCount(),
        ]);

        // 记录性能数据到数据库
        $this->recordPerformanceMetrics(array_merge($requestData, [
            'response_time' => $executionTime,
            'memory_usage' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'response_size' => $responseSize,
            'status_code' => $statusCode,
        ]));

        // 检查性能警告
        $this->checkPerformanceWarnings($executionTime, $memoryUsage, $statusCode);

        return $response;
    }

    /**
     * 记录性能指标到数据库
     */
    private function recordPerformanceMetrics(array $data): void
    {
        try {
            $sql = "
                INSERT INTO pay_performance_metrics 
                (endpoint, method, response_time, memory_usage, peak_memory, 
                 request_size, response_size, status_code, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->db->execute($sql, [
                $data['endpoint'],
                $data['method'],
                $data['response_time'],
                $data['memory_usage'],
                $data['peak_memory'],
                $data['request_size'],
                $data['response_size'],
                $data['status_code'],
                $data['user_id'],
                $data['ip_address'],
                $data['user_agent']
            ]);
        } catch (\Exception $e) {
            // 性能监控失败不应该影响主要功能
            error_log("性能监控记录失败: " . $e->getMessage());
        }
    }

    /**
     * 检查性能警告
     */
    private function checkPerformanceWarnings(float $responseTime, float $memoryUsage, int $statusCode): void
    {
        $warnings = [];

        // 响应时间警告
        if ($responseTime > 2000) { // 超过2秒
            $warnings[] = "响应时间过长: {$responseTime}ms";
        }

        // 内存使用警告
        if ($memoryUsage > 10240) { // 超过10MB
            $warnings[] = "内存使用过高: {$memoryUsage}KB";
        }

        // 错误状态码警告
        if ($statusCode >= 500) {
            $warnings[] = "服务器错误: {$statusCode}";
        }

        // 记录警告
        if (!empty($warnings)) {
            error_log("性能警告: " . implode(', ', $warnings));
        }
    }

    /**
     * 获取数据库查询次数（需要实现查询计数）
     */
    private function getQueryCount(): int
    {
        // 这里需要实现查询计数逻辑
        // 可以通过修改Database类来统计查询次数
        return 0;
    }

    /**
     * 获取性能统计
     */
    public static function getPerformanceStats(int $days = 7): array
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                endpoint,
                method,
                COUNT(*) as request_count,
                AVG(response_time) as avg_response_time,
                MAX(response_time) as max_response_time,
                AVG(memory_usage) as avg_memory_usage,
                COUNT(CASE WHEN status_code >= 400 THEN 1 END) as error_count
            FROM pay_performance_metrics 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY endpoint, method
            ORDER BY avg_response_time DESC
        ";
        
        return $db->findAll($sql, [$days]);
    }

    /**
     * 获取慢查询统计
     */
    public static function getSlowQueries(float $threshold = 1000): array
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                endpoint,
                method,
                response_time,
                memory_usage,
                created_at,
                user_id
            FROM pay_performance_metrics 
            WHERE response_time > ?
            ORDER BY response_time DESC
            LIMIT 100
        ";
        
        return $db->findAll($sql, [$threshold]);
    }

    /**
     * 清理过期性能数据
     */
    public static function cleanupExpiredData(int $days = 30): int
    {
        $db = Database::getInstance();
        
        $sql = "DELETE FROM pay_performance_metrics WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $db->execute($sql, [$days]);
        
        return $db->getAffectedRows();
    }

    /**
     * 软删除性能数据
     * 
     * @param int $id 性能数据ID
     * @return bool 删除结果
     */
    public static function softDeleteMetric(int $id): bool
    {
        $db = Database::getInstance();
        $sql = "UPDATE pay_performance_metrics SET is_del = 0, delete_time = NOW() WHERE id = ?";
        return $db->execute($sql, [$id]);
    }

    /**
     * 恢复软删除的性能数据
     * 
     * @param int $id 性能数据ID
     * @return bool 恢复结果
     */
    public static function restoreMetric(int $id): bool
    {
        $db = Database::getInstance();
        $sql = "UPDATE pay_performance_metrics SET is_del = 1, delete_time = NULL WHERE id = ?";
        return $db->execute($sql, [$id]);
    }

    /**
     * 永久删除性能数据（物理删除）
     * 
     * @param int $id 性能数据ID
     * @return bool 删除结果
     */
    public static function forceDeleteMetric(int $id): bool
    {
        $db = Database::getInstance();
        $sql = "DELETE FROM pay_performance_metrics WHERE id = ?";
        return $db->execute($sql, [$id]);
    }

    /**
     * 批量软删除性能数据
     * 
     * @param array $ids 性能数据ID数组
     * @return bool 删除结果
     */
    public static function batchSoftDeleteMetrics(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $db = Database::getInstance();
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE pay_performance_metrics SET is_del = 0, delete_time = NOW() WHERE id IN ({$placeholders})";
        return $db->execute($sql, $ids);
    }

    /**
     * 清理软删除的旧性能数据（物理删除）
     * 
     * @param int $days 天数
     * @return bool 清理结果
     */
    public static function cleanSoftDeletedMetrics(int $days = 7): bool
    {
        $db = Database::getInstance();
        $sql = "DELETE FROM pay_performance_metrics WHERE is_del = 0 AND delete_time < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $db->execute($sql, [$days]);
    }
}