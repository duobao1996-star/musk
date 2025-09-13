<?php

namespace app\support;

use Redis;
use RedisException;

/**
 * 缓存操作类
 * 提供Redis缓存和文件缓存的支持，支持自动降级
 */
class Cache
{
    private static ?self $instance = null;
    private ?Redis $redis = null;
    private array $config;

    /**
     * 私有构造函数，初始化缓存连接
     */
    private function __construct()
    {
        $this->config = config('redis.connections.cache');
        $this->connect();
    }

    /**
     * 获取缓存实例（单例模式）
     * 
     * @return self 缓存实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 连接Redis服务器
     */
    private function connect(): void
    {
        try {
            if (!class_exists('Redis')) {
                // Redis扩展未安装，使用文件缓存降级
                $this->redis = null;
                return;
            }
            
            $this->redis = new Redis();
            $this->redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
            
            if (!empty($this->config['password'])) {
                $this->redis->auth($this->config['password']);
            }
            
            $this->redis->select($this->config['database']);
            
        } catch (\Exception $e) {
            // Redis连接失败时使用文件缓存
            error_log("Redis连接失败: " . $e->getMessage());
            $this->redis = null;
        }
    }

    /**
     * 获取缓存
     */
    public function get($key, $default = null)
    {
        if (!$this->redis) {
            return $this->getFileCache($key, $default);
        }

        try {
            $value = $this->redis->get($key);
            return $value === false ? $default : unserialize($value);
        } catch (RedisException $e) {
            error_log("Redis获取失败: " . $e->getMessage());
            return $this->getFileCache($key, $default);
        }
    }

    /**
     * 设置缓存
     */
    public function set($key, $value, $ttl = 3600)
    {
        if (!$this->redis) {
            return $this->setFileCache($key, $value, $ttl);
        }

        try {
            return $this->redis->setex($key, $ttl, serialize($value));
        } catch (RedisException $e) {
            error_log("Redis设置失败: " . $e->getMessage());
            return $this->setFileCache($key, $value, $ttl);
        }
    }

    /**
     * 删除缓存
     */
    public function delete($key)
    {
        if (!$this->redis) {
            return $this->deleteFileCache($key);
        }

        try {
            return $this->redis->del($key);
        } catch (RedisException $e) {
            error_log("Redis删除失败: " . $e->getMessage());
            return $this->deleteFileCache($key);
        }
    }

    /**
     * 检查缓存是否存在
     */
    public function has($key)
    {
        if (!$this->redis) {
            return $this->hasFileCache($key);
        }

        try {
            return $this->redis->exists($key);
        } catch (RedisException $e) {
            error_log("Redis检查失败: " . $e->getMessage());
            return $this->hasFileCache($key);
        }
    }

    /**
     * 清空所有缓存
     */
    public function flush()
    {
        if (!$this->redis) {
            return $this->flushFileCache();
        }

        try {
            return $this->redis->flushdb();
        } catch (RedisException $e) {
            error_log("Redis清空失败: " . $e->getMessage());
            return $this->flushFileCache();
        }
    }

    /**
     * 获取或设置缓存
     */
    public function remember($key, $callback, $ttl = 3600)
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    /**
     * 文件缓存作为Redis的备用方案
     */
    private function getFileCache($key, $default = null)
    {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    private function setFileCache($key, $value, $ttl)
    {
        $file = $this->getCacheFile($key);
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return file_put_contents($file, serialize($data)) !== false;
    }

    private function deleteFileCache($key)
    {
        $file = $this->getCacheFile($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    private function hasFileCache($key)
    {
        $file = $this->getCacheFile($key);
        if (!file_exists($file)) {
            return false;
        }

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] < time()) {
            unlink($file);
            return false;
        }

        return true;
    }

    private function flushFileCache()
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
        return true;
    }

    private function getCacheFile($key)
    {
        $hash = md5($key);
        return runtime_path() . '/cache/' . substr($hash, 0, 2) . '/' . $hash . '.cache';
    }

    /**
     * 获取Redis实例
     */
    public function getRedis()
    {
        return $this->redis;
    }
}
