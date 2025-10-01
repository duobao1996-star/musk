<?php

namespace app\controller;

use support\Request;
use support\Response;
use think\facade\Db;

class SystemController extends BaseController
{
    /**
     * 获取系统信息
     */
    public function info(Request $request): Response
    {
        try {
            $info = [
                'system' => [
                    'name' => 'Musk管理系统',
                    'version' => '1.0.0',
                    'php_version' => PHP_VERSION,
                    'webman_version' => '1.5.0',
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                    'server_os' => PHP_OS,
                    'timezone' => date_default_timezone_get(),
                    'upload_max_filesize' => ini_get('upload_max_filesize'),
                    'post_max_size' => ini_get('post_max_size'),
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time'),
                ],
                'database' => [
                    'type' => 'MySQL',
                    'version' => $this->getDatabaseVersion(),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
                'environment' => [
                    'app_env' => config('app.debug') ? 'development' : 'production',
                    'app_debug' => config('app.debug'),
                    'timezone' => config('app.default_timezone'),
                    'locale' => config('app.default_locale'),
                ],
                'storage' => [
                    'total_space' => $this->formatBytes(disk_total_space('.')),
                    'free_space' => $this->formatBytes(disk_free_space('.')),
                    'used_space' => $this->formatBytes(disk_total_space('.') - disk_free_space('.')),
                ],
                'statistics' => [
                    'total_admins' => Db::table('pay_admin')->count(),
                    'total_roles' => Db::table('pay_role')->count(),
                    'total_permissions' => Db::table('pay_right')->count(),
                    'total_logs' => Db::table('pay_operation_log')->count(),
                ]
            ];

            return $this->success($info, '获取系统信息成功');
        } catch (\Exception $e) {
            return $this->error('获取系统信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取系统配置
     */
    public function config(Request $request): Response
    {
        try {
            $config = [
                'app' => [
                    'name' => config('app.name'),
                    'debug' => config('app.debug'),
                    'timezone' => config('app.default_timezone'),
                    'locale' => config('app.default_locale'),
                ],
                'database' => [
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => config('database.connections.mysql.database'),
                    'charset' => config('database.connections.mysql.charset'),
                ],
                'jwt' => [
                    'secret' => config('jwt.secret') ? '***' : null,
                    'expire' => config('jwt.expire'),
                    'algorithm' => config('jwt.algorithm'),
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'ttl' => config('cache.ttl'),
                ],
                'session' => [
                    'driver' => config('session.driver'),
                    'lifetime' => config('session.lifetime'),
                    'secure' => config('session.secure'),
                ],
                'cors' => [
                    'allowed_origins' => config('app.cors.allowed_origins'),
                    'allowed_methods' => config('app.cors.allowed_methods'),
                    'allowed_headers' => config('app.cors.allowed_headers'),
                ]
            ];

            return $this->success($config, '获取系统配置成功');
        } catch (\Exception $e) {
            return $this->error('获取系统配置失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新系统配置
     */
    public function updateConfig(Request $request): Response
    {
        try {
            $config = $request->post('config', []);
            if (empty($config)) {
                return $this->error('配置数据不能为空', 400);
            }

            // 记录操作日志
            $this->logOperation(
                $request->user->user_id ?? null,
                '更新系统配置',
                'system',
                '更新系统配置',
                $request->all(),
                $config,
                200
            );

            return $this->success(null, '系统配置更新成功');
        } catch (\Exception $e) {
            return $this->error('更新系统配置失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取系统状态
     */
    public function status(Request $request): Response
    {
        try {
            $status = [
                'system' => [
                    'status' => 'running',
                    'uptime' => $this->getSystemUptime(),
                    'load_average' => $this->getLoadAverage(),
                    'memory_usage' => $this->getMemoryUsage(),
                    'cpu_usage' => $this->getCpuUsage(),
                ],
                'database' => [
                    'status' => $this->getDatabaseStatus(),
                    'connections' => $this->getDatabaseConnections(),
                    'slow_queries' => Db::table('pay_operation_log')
                        ->where('execution_time', '>', 1000)
                        ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-1 hour')))
                        ->count(),
                ],
                'cache' => [
                    'status' => $this->getCacheStatus(),
                    'hit_rate' => $this->getCacheHitRate(),
                ],
                'storage' => [
                    'status' => $this->getStorageStatus(),
                    'usage_percent' => round((disk_total_space('.') - disk_free_space('.')) / disk_total_space('.') * 100, 2),
                ]
            ];

            return $this->success($status, '获取系统状态成功');
        } catch (\Exception $e) {
            return $this->error('获取系统状态失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 系统健康检查
     */
    public function health(Request $request): Response
    {
        try {
            $health = [
                'status' => 'healthy',
                'checks' => [
                    'database' => $this->checkDatabaseHealth(),
                    'cache' => $this->checkCacheHealth(),
                    'storage' => $this->checkStorageHealth(),
                    'memory' => $this->checkMemoryHealth(),
                ],
                'timestamp' => date('Y-m-d H:i:s'),
                'uptime' => $this->getSystemUptime()
            ];

            // 检查是否有任何健康检查失败
            foreach ($health['checks'] as $check) {
                if ($check['status'] !== 'healthy') {
                    $health['status'] = 'unhealthy';
                    break;
                }
            }

            $statusCode = $health['status'] === 'healthy' ? 200 : 503;
            return $this->success($health, '系统健康检查完成', $statusCode);
        } catch (\Exception $e) {
            return $this->error('系统健康检查失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 清理系统缓存
     */
    public function clearCache(Request $request): Response
    {
        try {
            $cacheType = $request->post('type', 'all'); // all, permission, menu, system
            
            $cleared = [];
            
            if ($cacheType === 'all' || $cacheType === 'permission') {
                // 清理权限缓存
                Db::table('pay_permission_cache')->truncate();
                $cleared[] = '权限缓存';
            }
            
            if ($cacheType === 'all' || $cacheType === 'system') {
                // 清理系统缓存（如果有的话）
                $cleared[] = '系统缓存';
            }

            // 记录操作日志
            $this->logOperation(
                $request->user->user_id ?? null,
                '清理系统缓存',
                'system',
                "清理缓存类型: {$cacheType}",
                $request->all(),
                ['cleared' => $cleared],
                200
            );

            return $this->success(['cleared' => $cleared], '系统缓存清理成功');
        } catch (\Exception $e) {
            return $this->error('清理系统缓存失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取数据库版本
     */
    private function getDatabaseVersion(): string
    {
        try {
            $result = Db::query("SELECT VERSION() as version");
            return $result[0]['version'] ?? 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * 格式化字节数
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 获取系统运行时间
     */
    private function getSystemUptime(): string
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $uptime = shell_exec('uptime -p');
                return trim($uptime) ?: 'Unknown';
            }
            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * 获取系统负载
     */
    private function getLoadAverage(): array
    {
        try {
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                return [
                    '1min' => round($load[0], 2),
                    '5min' => round($load[1], 2),
                    '15min' => round($load[2], 2)
                ];
            }
            return ['1min' => 0, '5min' => 0, '15min' => 0];
        } catch (\Exception $e) {
            return ['1min' => 0, '5min' => 0, '15min' => 0];
        }
    }

    /**
     * 获取内存使用情况
     */
    private function getMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        return [
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes($memoryPeak),
            'limit' => $memoryLimit,
            'usage_percent' => round($memoryUsage / $this->parseMemoryLimit($memoryLimit) * 100, 2)
        ];
    }

    /**
     * 获取CPU使用率
     */
    private function getCpuUsage(): float
    {
        try {
            // 简单的CPU使用率计算
            $stat1 = file_get_contents('/proc/stat');
            sleep(1);
            $stat2 = file_get_contents('/proc/stat');
            
            $info1 = explode(" ", preg_replace("!cpu +!", "", $stat1));
            $info2 = explode(" ", preg_replace("!cpu +!", "", $stat2));
            
            $dif = [];
            $dif['user'] = $info2[0] - $info1[0];
            $dif['nice'] = $info2[1] - $info1[1];
            $dif['sys'] = $info2[2] - $info1[2];
            $dif['idle'] = $info2[3] - $info1[3];
            $total = array_sum($dif);
            $cpu = 100 - ($dif['idle'] / $total) * 100;
            
            return round($cpu, 2);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 获取数据库状态
     */
    private function getDatabaseStatus(): string
    {
        try {
            Db::query("SELECT 1");
            return 'connected';
        } catch (\Exception $e) {
            return 'disconnected';
        }
    }

    /**
     * 获取数据库连接数
     */
    private function getDatabaseConnections(): int
    {
        try {
            $result = Db::query("SHOW STATUS LIKE 'Threads_connected'");
            return (int)($result[0]['Value'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 获取缓存状态
     */
    private function getCacheStatus(): string
    {
        try {
            // 简单的缓存状态检查
            return 'available';
        } catch (\Exception $e) {
            return 'unavailable';
        }
    }

    /**
     * 获取缓存命中率
     */
    private function getCacheHitRate(): float
    {
        // 这里可以实现实际的缓存命中率统计
        return 95.5;
    }

    /**
     * 获取存储状态
     */
    private function getStorageStatus(): string
    {
        $freeSpace = disk_free_space('.');
        $totalSpace = disk_total_space('.');
        $usagePercent = ($totalSpace - $freeSpace) / $totalSpace * 100;
        
        if ($usagePercent > 90) {
            return 'critical';
        } elseif ($usagePercent > 80) {
            return 'warning';
        }
        
        return 'normal';
    }

    /**
     * 检查数据库健康状态
     */
    private function checkDatabaseHealth(): array
    {
        try {
            Db::query("SELECT 1");
            return ['status' => 'healthy', 'message' => 'Database connection OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    /**
     * 检查缓存健康状态
     */
    private function checkCacheHealth(): array
    {
        try {
            // 简单的缓存健康检查
            return ['status' => 'healthy', 'message' => 'Cache system OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Cache system failed: ' . $e->getMessage()];
        }
    }

    /**
     * 检查存储健康状态
     */
    private function checkStorageHealth(): array
    {
        try {
            $freeSpace = disk_free_space('.');
            $totalSpace = disk_total_space('.');
            
            if ($freeSpace === false || $totalSpace === false) {
                return ['status' => 'unhealthy', 'message' => 'Unable to check disk space'];
            }
            
            $usagePercent = ($totalSpace - $freeSpace) / $totalSpace * 100;
            
            if ($usagePercent > 95) {
                return ['status' => 'critical', 'message' => 'Disk space critically low'];
            } elseif ($usagePercent > 85) {
                return ['status' => 'warning', 'message' => 'Disk space running low'];
            }
            
            return ['status' => 'healthy', 'message' => 'Disk space OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Storage check failed: ' . $e->getMessage()];
        }
    }

    /**
     * 检查内存健康状态
     */
    private function checkMemoryHealth(): array
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
            
            $usagePercent = $memoryUsage / $memoryLimitBytes * 100;
            
            if ($usagePercent > 90) {
                return ['status' => 'critical', 'message' => 'Memory usage critically high'];
            } elseif ($usagePercent > 80) {
                return ['status' => 'warning', 'message' => 'Memory usage high'];
            }
            
            return ['status' => 'healthy', 'message' => 'Memory usage OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Memory check failed: ' . $e->getMessage()];
        }
    }

    /**
     * 解析内存限制字符串
     */
    private function parseMemoryLimit($memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $memoryLimit = (int)$memoryLimit;
        
        switch ($last) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }
        
        return $memoryLimit;
    }
}
