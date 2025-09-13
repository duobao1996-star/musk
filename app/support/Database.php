<?php

namespace app\support;

use PDO;

/**
 * 数据库操作类
 * 提供统一的数据库连接和查询方法，支持连接池和缓存
 */
class Database
{
    private static ?self $instance = null;
    private ?PDO $pdo = null;
    private ?ConnectionPool $connectionPool = null;
    private ?Cache $cache = null;
    private bool $usePool = true;

    /**
     * 私有构造函数，初始化数据库连接
     */
    private function __construct()
    {
        $this->connectionPool = ConnectionPool::getInstance();
        $this->cache = Cache::getInstance();
        
        // 连接池不可用时使用传统连接方式
        if (!$this->connectionPool) {
            $this->usePool = false;
            $this->initDirectConnection();
        }
    }

    /**
     * 初始化直接数据库连接
     */
    private function initDirectConnection(): void
    {
        $config = config('database.connections.mysql');
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
        
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => 30,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]);
    }

    /**
     * 获取数据库实例（单例模式）
     * 
     * @return self 数据库实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取PDO连接对象
     * 
     * @return PDO|null PDO连接对象
     */
    public function getPdo(): ?PDO
    {
        if ($this->usePool) {
            return $this->connectionPool->getConnection();
        }
        return $this->pdo;
    }

    /**
     * 查询单条记录（带缓存）
     */
    public function find($sql, $params = [], $cacheKey = null, $cacheTtl = 300)
    {
        // 尝试从缓存获取
        if ($cacheKey && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $pdo = $this->getPdo();
        if (!$pdo) {
            throw new \Exception('无法获取数据库连接');
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            
            // 缓存结果
            if ($cacheKey && $this->cache && $result !== false) {
                $this->cache->set($cacheKey, $result, $cacheTtl);
            }
            
            return $result;
        } finally {
            if ($this->usePool) {
                $this->connectionPool->releaseConnection($pdo);
            }
        }
    }

    /**
     * 查询多条记录（带缓存）
     */
    public function findAll($sql, $params = [], $cacheKey = null, $cacheTtl = 300)
    {
        // 尝试从缓存获取
        if ($cacheKey && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $pdo = $this->getPdo();
        if (!$pdo) {
            throw new \Exception('无法获取数据库连接');
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll();
            
            // 缓存结果
            if ($cacheKey && $this->cache) {
                $this->cache->set($cacheKey, $result, $cacheTtl);
            }
            
            return $result;
        } finally {
            if ($this->usePool) {
                $this->connectionPool->releaseConnection($pdo);
            }
        }
    }

    /**
     * 执行插入、更新、删除操作
     */
    public function execute($sql, $params = [])
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            throw new \Exception('无法获取数据库连接');
        }

        try {
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // 清除相关缓存
            $this->clearRelatedCache($sql);
            
            return $result;
        } finally {
            if ($this->usePool) {
                $this->connectionPool->releaseConnection($pdo);
            }
        }
    }

    /**
     * 获取最后插入的ID
     */
    public function lastInsertId()
    {
        $pdo = $this->getPdo();
        if (!$pdo) {
            throw new \Exception('无法获取数据库连接');
        }

        try {
            return $pdo->lastInsertId();
        } finally {
            if ($this->usePool) {
                $this->connectionPool->releaseConnection($pdo);
            }
        }
    }

    /**
     * 清除相关缓存
     */
    private function clearRelatedCache($sql)
    {
        if (!$this->cache) {
            return;
        }

        $sql = strtolower($sql);
        
        // 根据SQL类型清除相关缓存
        if (strpos($sql, 'insert') === 0 || strpos($sql, 'update') === 0 || strpos($sql, 'delete') === 0) {
            // 清除所有缓存（简单实现，生产环境可以更精确）
            $this->cache->flush();
        }
    }

    /**
     * 获取连接池状态
     */
    public function getPoolStatus()
    {
        if ($this->usePool && $this->connectionPool) {
            return $this->connectionPool->getPoolStatus();
        }
        return ['status' => 'direct_connection'];
    }
}
