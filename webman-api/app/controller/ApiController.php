<?php

namespace app\controller;

use support\Request;
use support\Response;

class ApiController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->success([
            'version' => '2.0',
            'server' => 'Webman',
            'php_version' => PHP_VERSION,
            'framework' => 'Webman 2.0'
        ], 'Webman API 2.0 运行正常');
    }

    public function health(Request $request): Response
    {
        return $this->success(['status' => 'ok', 'time' => time()], 'healthy');
    }

    public function ready(Request $request): Response
    {
        // 后续可加入依赖检查（DB/Redis）
        return $this->success(['ready' => true, 'time' => time()], 'ready');
    }
}
