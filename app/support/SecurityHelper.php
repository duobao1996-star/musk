<?php

namespace app\support;

/**
 * 安全辅助类
 * 提供各种安全相关的工具方法，包括密码加密、令牌生成等
 */
class SecurityHelper
{
    /**
     * 生成安全令牌
     * 使用PHP 8.2优化的随机字节生成
     * 
     * @param int $length 令牌长度
     * @return string 十六进制令牌
     */
    public static function generateSecureToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * 生成密码盐值
     * 
     * @return string 十六进制盐值
     */
    public static function generatePasswordSalt(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * 生成API密钥
     * 
     * @return string API密钥
     */
    public static function generateApiKey(): string
    {
        return 'ak_' . self::generateSecureToken(24);
    }
    
    /**
     * 生成JWT密钥
     */
    public static function generateJwtSecret(): string
    {
        return base64_encode(random_bytes(64));
    }
    
    /**
     * 安全的密码哈希 - 使用PHP 8.2优化
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3,         // 3 threads
        ]);
    }
    
    /**
     * 验证密码
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * 生成CSRF令牌
     */
    public static function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * 安全的字符串比较
     */
    public static function secureCompare(string $a, string $b): bool
    {
        if (strlen($a) !== strlen($b)) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * 清理输入数据
     */
    public static function sanitizeInput(mixed $input): mixed
    {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        return $input;
    }
    
    /**
     * 验证邮箱格式
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * 验证URL格式
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * 生成文件哈希
     */
    public static function generateFileHash(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('文件不存在: ' . $filePath);
        }
        
        return hash_file('sha256', $filePath);
    }
    
    /**
     * 生成数据哈希
     */
    public static function generateDataHash(mixed $data): string
    {
        return hash('sha256', serialize($data));
    }
}
