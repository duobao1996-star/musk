<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    // 全局中间件
    '' => [
        app\middleware\SecurityMiddleware::class,
        app\middleware\CorsMiddleware::class,
        app\middleware\RateLimitMiddleware::class,
        app\middleware\PerformanceMiddleware::class,
        app\middleware\CsrfMiddleware::class,
        app\middleware\OperationLogMiddleware::class,
    ],
    // 需要认证的路由中间件
    'api' => [
        app\middleware\JwtMiddleware::class,
        app\middleware\PermissionMiddleware::class,
    ],
    // 需要认证的API路由中间件
    'api_auth' => [
        app\middleware\JwtMiddleware::class,
        app\middleware\PermissionMiddleware::class,
    ],
];