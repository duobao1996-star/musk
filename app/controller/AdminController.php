<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\User;

class AdminController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * 获取管理员列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $keyword = $request->get('keyword', '');
            
            $offset = ($page - 1) * $limit;
            
            // 构建查询条件
            $where = "WHERE status = 1";
            $params = [];
            
            if (!empty($keyword)) {
                $where .= " AND (user_name LIKE ? OR email LIKE ?)";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM pay_admin {$where}";
            $total = $this->userModel->find($countSql, $params)['total'];
            
            // 查询数据
            $sql = "SELECT id, user_name, email, role_id, ctime, etime, status, phone FROM pay_admin {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}";
            $admins = $this->userModel->findAll($sql, $params);
            
            return $this->paginate($admins, $total, $page, $limit);
            
        } catch (\Exception $e) {
            return $this->error('获取管理员列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单个管理员信息
     */
    public function show(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('管理员ID不能为空', 400);
        }
        
        $admin = $this->userModel->findAdminById($id);
        
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }
        
        return $this->success($admin, '获取管理员信息成功');
    }

    /**
     * 创建管理员
     */
    public function store(Request $request): Response
    {
        // 验证参数
        $errors = $this->validate($request, [
            'user_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            $data = [
                'user_name' => $request->post('user_name'),
                'email' => $request->post('email'),
                'password' => $request->post('password'),
                'role_id' => $request->post('role_id', 1)
            ];
            
            $id = $this->userModel->createAdmin($data);
            
            return $this->success(['id' => $id], '管理员创建成功', 201);
            
        } catch (\Exception $e) {
            return $this->error('创建管理员失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新管理员信息
     */
    public function update(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('管理员ID不能为空', 400);
        }
        
        // 检查管理员是否存在
        $admin = $this->userModel->findAdminById($id);
        if (!$admin) {
            return $this->error('管理员不存在', 404);
        }
        
        try {
            $data = [];
            
            if ($request->post('user_name')) {
                $data['user_name'] = $request->post('user_name');
            }
            
            if ($request->post('email')) {
                $data['email'] = $request->post('email');
            }
            
            if ($request->post('password')) {
                $data['password'] = $request->post('password');
            }
            
            if (empty($data)) {
                return $this->error('没有要更新的数据', 400);
            }
            
            $this->userModel->updateAdmin($id, $data);
            
            return $this->success(null, '管理员信息更新成功');
            
        } catch (\Exception $e) {
            return $this->error('更新管理员失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除管理员（软删除）
     */
    public function destroy(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('管理员ID不能为空', 400);
        }
        
        try {
            // 软删除：将status设置为0
            $sql = "UPDATE pay_admin SET status = 0, etime = NOW() WHERE id = ?";
            $this->userModel->execute($sql, [$id]);
            
            return $this->success(null, '管理员删除成功');
            
        } catch (\Exception $e) {
            return $this->error('删除管理员失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取管理员统计信息
     */
    public function stats(Request $request): Response
    {
        try {
            $stats = [];
            
            // 总管理员数
            $total = $this->userModel->find("SELECT COUNT(*) as total FROM pay_admin WHERE status = 1")['total'];
            $stats['total_admins'] = $total;
            
            // 今日新增
            $today = $this->userModel->find("SELECT COUNT(*) as total FROM pay_admin WHERE status = 1 AND DATE(ctime) = CURDATE()")['total'];
            $stats['today_new'] = $today;
            
            // 按角色统计
            $roleStats = $this->userModel->findAll("SELECT role_id, COUNT(*) as count FROM pay_admin WHERE status = 1 GROUP BY role_id");
            $stats['role_stats'] = $roleStats;
            
            return $this->success($stats, '获取统计信息成功');
            
        } catch (\Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage(), 500);
        }
    }
}
