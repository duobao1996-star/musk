<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class RateLimitMiddleware implements MiddlewareInterface
{
    private $maxRequests;
    private $window;
    private $storage = [];

    public function __construct()
    {
        $this->maxRequests = config('app.rate_limit.max_requests', 100);
        $this->window = config('app.rate_limit.window', 60);
    }

    public function process(Request $request, callable $handler): Response
    {
        $ip = $this->getClientIp($request);
        $key = "rate_limit:{$ip}";
        $now = time();
        
        // 清理过期记录
        $this->cleanExpiredRecords($now);
        
        // 检查当前IP的请求次数
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = [];
        }
        
        // 移除窗口期外的记录
        $this->storage[$key] = array_filter($this->storage[$key], function($timestamp) use ($now) {
            return $timestamp > $now - $this->window;
        });
        
        // 检查是否超过限制
        if (count($this->storage[$key]) >= $this->maxRequests) {
            return json([
                'code' => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data' => null,
                'timestamp' => $now
            ], 429);
        }
        
        // 记录当前请求
        $this->storage[$key][] = $now;
        
        return $handler($request);
    }
    
    private function getClientIp(Request $request)
    {
        $ip = $request->header('X-Forwarded-For');
        if ($ip) {
            $ips = explode(',', $ip);
            return trim($ips[0]);
        }
        
        $ip = $request->header('X-Real-IP');
        if ($ip) {
            return $ip;
        }
        
        return $request->getRemoteIp();
    }
    
    private function cleanExpiredRecords($now)
    {
        foreach ($this->storage as $key => $records) {
            $this->storage[$key] = array_filter($records, function($timestamp) use ($now) {
                return $timestamp > $now - $this->window;
            });
            
            if (empty($this->storage[$key])) {
                unset($this->storage[$key]);
            }
        }
    }
}
