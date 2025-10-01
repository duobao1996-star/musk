<?php

namespace app\controller;

use support\Request;
use support\Response;
use think\facade\Db;
use app\model\OperationLog;
use app\support\SecurityHelper;

class AdminController extends BaseController
{
    /**
     * 列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $keyword = trim((string)$request->get('keyword', ''));
            $status = $request->get('status');

            $query = Db::table('pay_admin')->whereRaw('1=1');
            if ($keyword !== '') {
                $kw = "%{$keyword}%";
                $query->where(function($q) use ($kw) {
                    $q->whereLike('user_name', $kw)->orWhereLike('email', $kw);
                });
            }
            if ($status !== null && $status !== '') {
                $query->where('status', (int)$status);
            }

            $total = (clone $query)->count();
            $rows = $query->order('id','desc')->limit(($page-1)*$limit, $limit)->select()->toArray();

            return $this->paginate($rows, $total, $page, $limit, '获取管理员列表成功');
        } catch (\Exception $e) {
            return $this->error('获取管理员列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 详情
     */
    public function show(Request $request): Response
    {
        try {
            $id = (int)$request->route('id');
            if ($id <= 0) {
                return $this->error('无效的管理员ID', 400);
            }

            $admin = Db::table('pay_admin')->where('id', $id)->find();
            if (!$admin) {
                return $this->error('管理员不存在', 404);
            }

            // 移除敏感信息
            unset($admin['user_password']);

            return $this->success($admin, '获取管理员详情成功');
        } catch (\Exception $e) {
            return $this->error('获取管理员详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量创建管理员
     */
    public function batchCreate(Request $request): Response
    {
        try {
            $admins = $request->post('admins', []);
            if (empty($admins) || !is_array($admins)) {
                return $this->error('参数错误', 400);
            }

            $createdCount = 0;
            $errors = [];

            foreach ($admins as $index => $adminData) {
                try {
                    // 验证必填字段
                    if (empty($adminData['user_name']) || empty($adminData['email']) || empty($adminData['user_password'])) {
                        $errors[] = "第" . ($index + 1) . "条记录缺少必填字段";
                        continue;
                    }

                    // 检查用户名和邮箱是否已存在
                    $exists = Db::table('pay_admin')
                        ->where('user_name', $adminData['user_name'])
                        ->whereOr('email', $adminData['email'])
                        ->find();

                    if ($exists) {
                        $errors[] = "第" . ($index + 1) . "条记录用户名或邮箱已存在";
                        continue;
                    }

                    // 创建管理员
                    $admin = [
                        'user_name' => $adminData['user_name'],
                        'email' => $adminData['email'],
                        'user_password' => SecurityHelper::hashPassword($adminData['user_password']),
                        'phone' => $adminData['phone'] ?? null,
                        'role_id' => (int)($adminData['role_id'] ?? 1),
                        'status' => (int)($adminData['status'] ?? 1),
                        'Remarks' => $adminData['Remarks'] ?? null,
                        'ctime' => date('Y-m-d H:i:s'),
                        'etime' => date('Y-m-d H:i:s')
                    ];

                    $adminId = Db::table('pay_admin')->insertGetId($admin);

                    // 记录操作日志
                    $this->logOperation(
                        $request->user->user_id ?? null,
                        '批量创建管理员',
                        'admin',
                        "批量创建管理员: {$adminData['user_name']}",
                        $request->all(),
                        ['admin_id' => $adminId],
                        200
                    );

                    $createdCount++;
                } catch (\Exception $e) {
                    $errors[] = "第" . ($index + 1) . "条记录创建失败: " . $e->getMessage();
                }
            }

            $result = [
                'created_count' => $createdCount,
                'total_count' => count($admins),
                'errors' => $errors
            ];

            return $this->success($result, "批量创建完成，成功创建 {$createdCount} 个管理员");
        } catch (\Exception $e) {
            return $this->error('批量创建管理员失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取管理员统计信息
     */
    public function stats(Request $request): Response
    {
        try {
            $stats = [
                'total_admins' => Db::table('pay_admin')->count(),
                'active_admins' => Db::table('pay_admin')->where('status', 1)->count(),
                'inactive_admins' => Db::table('pay_admin')->where('status', 0)->count(),
                'admins_by_role' => Db::table('pay_admin')
                    ->alias('a')
                    ->leftJoin(['pay_role' => 'r'], 'a.role_id = r.id')
                    ->field('r.role_name, COUNT(*) as count')
                    ->group('a.role_id')
                    ->select()
                    ->toArray(),
                'recent_logins' => Db::table('pay_admin')
                    ->where('last_login_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->count(),
                'login_today' => Db::table('pay_admin')
                    ->where('last_login_at', '>=', date('Y-m-d'))
                    ->count()
            ];

            return $this->success($stats, '获取管理员统计信息成功');
        } catch (\Exception $e) {
            return $this->error('获取管理员统计信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取管理员选项列表（用于下拉选择）
     */
    public function options(Request $request): Response
    {
        try {
            $keyword = trim((string)$request->get('keyword', ''));
            $roleId = $request->get('role_id');
            $status = $request->get('status');

            $query = Db::table('pay_admin')
                ->alias('a')
                ->leftJoin(['pay_role' => 'r'], 'a.role_id = r.id')
                ->field('a.id, a.user_name, a.email, r.role_name')
                ->where('a.status', 1);

            if ($keyword !== '') {
                $kw = "%{$keyword}%";
                $query->where(function($q) use ($kw) {
                    $q->whereLike('a.user_name', $kw)->orWhereLike('a.email', $kw);
                });
            }

            if ($roleId !== null && $roleId !== '') {
                $query->where('a.role_id', (int)$roleId);
            }

            $admins = $query->limit(100)->select()->toArray();

            $options = array_map(function($admin) {
                return [
                    'value' => $admin['id'],
                    'label' => $admin['user_name'],
                    'email' => $admin['email'],
                    'role_name' => $admin['role_name']
                ];
            }, $admins);

            return $this->success($options, '获取管理员选项成功');
        } catch (\Exception $e) {
            return $this->error('获取管理员选项失败: ' . $e->getMessage(), 500);
        }
    }

    /** 新增 */
    public function store(Request $request): Response
    {
        $errors = $this->validate($request, [
            'user_name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);
        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }
        try {
            $exists = Db::table('pay_admin')->where('user_name', $request->post('user_name'))->count();
            if ($exists > 0) return $this->error('用户名已存在', 400);

            $id = Db::table('pay_admin')->insertGetId([
                'user_name' => $request->post('user_name'),
                'user_password' => SecurityHelper::hashPassword($request->post('password')),
                'email' => $request->post('email'),
                'role_id' => (int)$request->post('role_id', 1),
                'status' => (int)$request->post('status', 1),
                'ctime' => Db::raw('NOW()')
            ]);
            // 日志
            $op = new OperationLog();
            $user = $request->user ?? null;
            $op->logOperation($user->user_id ?? null, $user->username ?? '', 'create', 'admin', '创建管理员', [
                'method' => $request->method(),
                'url' => $request->path(),
                'params' => json_encode(['id' => $id, 'user_name' => $request->post('user_name')], JSON_UNESCAPED_UNICODE),
                'ip' => $request->getRealIp(),
                'user_agent' => $request->header('User-Agent')
            ], ['code' => 200, 'message' => '创建成功']);
            return $this->success(['id' => $id], '创建成功');
        } catch (\Exception $e) {
            return $this->error('创建失败: ' . $e->getMessage(), 500);
        }
    }

    /** 更新 */
    public function update(Request $request, $id = null): Response
    {
        if (!$id) return $this->error('ID不能为空', 400);
        try {
            $update = [];
            if ($request->post('user_name')) $update['user_name'] = $request->post('user_name');
            if ($request->post('email')) $update['email'] = $request->post('email');
            if ($request->post('role_id') !== null) $update['role_id'] = (int)$request->post('role_id');
            if ($request->post('status') !== null) $update['status'] = (int)$request->post('status');
            if (empty($update)) return $this->error('无可更新字段', 400);
            $update['etime'] = Db::raw('NOW()');
            $ok = Db::table('pay_admin')->where('id', $id)->update($update) > 0;
            if ($ok) {
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'update', 'admin', '更新管理员', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id, 'fields' => array_keys($update)], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '更新成功']);
                return $this->success([], '更新成功');
            }
            return $this->error('更新失败', 500);
        } catch (\Exception $e) {
            return $this->error('更新失败: ' . $e->getMessage(), 500);
        }
    }

    /** 重置密码 */
    public function resetPassword(Request $request, $id = null): Response
    {
        if (!$id) return $this->error('ID不能为空', 400);
        $password = $request->post('password');
        if (!$password || strlen($password) < 6) return $this->error('密码至少6位', 400);
        try {
            $ok = Db::table('pay_admin')->where('id', $id)->update([
                'user_password' => SecurityHelper::hashPassword($password),
                'etime' => Db::raw('NOW()')
            ]) > 0;
            if ($ok) {
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'update', 'admin', '重置管理员密码', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '重置密码成功']);
                return $this->success([], '重置密码成功');
            }
            return $this->error('重置密码失败', 500);
        } catch (\Exception $e) {
            return $this->error('重置密码失败: ' . $e->getMessage(), 500);
        }
    }

    /** 启停 */
    public function toggleStatus(Request $request, $id = null): Response
    {
        if (!$id) return $this->error('ID不能为空', 400);
        try {
            $row = Db::table('pay_admin')->where('id', $id)->find();
            if (!$row) return $this->error('不存在', 404);
            $current = (int)$row['status'];
            $new = $current === 1 ? 0 : 1;
            $update = ['status' => $new, 'etime' => Db::raw('NOW()')];
            // 如果禁用账号，立即清空其当前token，强制下线
            if ($new === 0) {
                $update['current_token'] = null;
                $update['token_expires_at'] = Db::raw('NOW()');
                $update['token_created_at'] = null;
            }
            Db::table('pay_admin')->where('id', $id)->update($update);

            // 操作日志：切换管理员状态
            $op = new OperationLog();
            $user = $request->user ?? null;
            $op->logOperation($user->user_id ?? null, $user->username ?? '', 'update', 'admin', '切换管理员状态', [
                'method' => $request->method(),
                'url' => $request->path(),
                'params' => json_encode(['id' => $id, 'new_status' => $new], JSON_UNESCAPED_UNICODE),
                'ip' => $request->getRealIp(),
                'user_agent' => $request->header('User-Agent')
            ], [
                'code' => 200,
                'message' => '状态已更新'
            ]);
            // 返回最新状态，防止并发下客户端状态不同步
            $latest = Db::table('pay_admin')->where('id', $id)->value('status');
            return $this->success(['status' => (int)$latest], '状态已更新');
        } catch (\Exception $e) {
            return $this->error('更新失败: ' . $e->getMessage(), 500);
        }
    }

    /** 删除（软删除：status=0） */
    public function destroy(Request $request, $id = null): Response
    {
        if (!$id) return $this->error('ID不能为空', 400);
        try {
            $ok = Db::table('pay_admin')->where('id', $id)->update(['status' => 0, 'etime' => Db::raw('NOW()')]) > 0;
            if ($ok) {
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'delete', 'admin', '删除管理员', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '删除成功']);
                return $this->success([], '删除成功');
            }
            return $this->error('删除失败', 500);
        } catch (\Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage(), 500);
        }
    }
    
}


