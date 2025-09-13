<?php

namespace app\support;

use PDO;
use PDOException;

class ConnectionPool
{
    private static $instance = null;
    private $pool = [];
    private $maxConnections = 10;
    private $minConnections = 2;
    private $currentConnections = 0;
    private $config;
    private $mutex;

    private function __construct()
    {
        $this->config = config('database.connections.mysql');
        $this->maxConnections = getenv('DB_MAX_CONNECTIONS') ?: 10;
        $this->minConnections = getenv('DB_MIN_CONNECTIONS') ?: 2;
        $this->mutex = sem_get(ftok(__FILE__, 'a'));
        
        // 初始化最小连接数
        $this->initializePool();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializePool()
    {
        for ($i = 0; $i < $this->minConnections; $i++) {
            $connection = $this->createConnection();
            if ($connection) {
                $this->pool[] = $connection;
                $this->currentConnections++;
            }
        }
    }

    private function createConnection()
    {
        try {
            $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $pdo = new PDO($dsn, $this->config['username'], $this->config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false, // 连接池中不使用持久连接
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_TIMEOUT => 30,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ]);

            return $pdo;
        } catch (PDOException $e) {
            error_log("数据库连接创建失败: " . $e->getMessage());
            return null;
        }
    }

    public function getConnection()
    {
        sem_acquire($this->mutex);
        
        try {
            // 尝试从池中获取连接
            if (!empty($this->pool)) {
                $connection = array_pop($this->pool);
                sem_release($this->mutex);
                return $connection;
            }

            // 池中没有连接，尝试创建新连接
            if ($this->currentConnections < $this->maxConnections) {
                $connection = $this->createConnection();
                if ($connection) {
                    $this->currentConnections++;
                    sem_release($this->mutex);
                    return $connection;
                }
            }

            // 无法获取连接，等待或返回null
            sem_release($this->mutex);
            return null;
            
        } catch (\Exception $e) {
            sem_release($this->mutex);
            error_log("获取数据库连接失败: " . $e->getMessage());
            return null;
        }
    }

    public function releaseConnection($connection)
    {
        if (!$connection) {
            return;
        }

        sem_acquire($this->mutex);
        
        try {
            // 检查连接是否还有效
            if ($this->isConnectionValid($connection)) {
                $this->pool[] = $connection;
            } else {
                $this->currentConnections--;
            }
        } catch (\Exception $e) {
            error_log("释放数据库连接失败: " . $e->getMessage());
            $this->currentConnections--;
        } finally {
            sem_release($this->mutex);
        }
    }

    private function isConnectionValid($connection)
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getPoolStatus()
    {
        return [
            'available' => count($this->pool),
            'current' => $this->currentConnections,
            'max' => $this->maxConnections,
            'min' => $this->minConnections
        ];
    }

    public function closeAllConnections()
    {
        sem_acquire($this->mutex);
        
        try {
            foreach ($this->pool as $connection) {
                $connection = null;
            }
            $this->pool = [];
            $this->currentConnections = 0;
        } finally {
            sem_release($this->mutex);
        }
    }

    public function __destruct()
    {
        $this->closeAllConnections();
        if ($this->mutex && is_resource($this->mutex)) {
            try {
                sem_release($this->mutex);
            } catch (\Exception $e) {
                // 忽略信号量释放错误，避免进程退出时的致命错误
            }
        }
    }
}
