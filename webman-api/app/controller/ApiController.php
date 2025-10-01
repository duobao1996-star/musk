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

    public function dashboardStats(Request $request): Response
    {
        try {
            // 获取统计数据
            $stats = [
                'users' => \think\facade\Db::table('pay_admin')->count(),
                'roles' => \think\facade\Db::table('pay_role')->where('is_del', 1)->count(),
                'permissions' => \think\facade\Db::table('pay_right')->where('is_del', 1)->count(),
                'logs' => \think\facade\Db::table('pay_operation_log')->count(),
            ];

            return $this->success($stats, '获取仪表盘统计成功');
        } catch (\Exception $e) {
            return $this->error('获取统计数据失败: ' . $e->getMessage(), 500);
        }
    }
}
