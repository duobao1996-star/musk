<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\User;

class MerchantController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * 获取商户/代理列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $keyword = $request->get('keyword', '');
            $user_type = $request->get('user_type', ''); // 0: 商户, 1: 代理
            
            $offset = ($page - 1) * $limit;
            
            // 构建查询条件
            $where = "WHERE status = 1";
            $params = [];
            
            if (!empty($keyword)) {
                $where .= " AND (user_name LIKE ? OR email LIKE ?)";
                $params[] = "%{$keyword}%";
                $params[] = "%{$keyword}%";
            }
            
            if ($user_type !== '') {
                $where .= " AND user_type = ?";
                $params[] = $user_type;
            }
            
            // 查询总数
            $countSql = "SELECT COUNT(*) as total FROM pay_user {$where}";
            $total = $this->userModel->find($countSql, $params)['total'];
            
            // 查询数据
            $sql = "SELECT id, user_name, email, user_type, reg_time, status, account_max_money, api_code FROM pay_user {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}";
            $merchants = $this->userModel->findAll($sql, $params);
            
            // 处理用户类型显示
            foreach ($merchants as &$merchant) {
                $merchant['user_type_text'] = $merchant['user_type'] == 0 ? '商户' : '代理';
            }
            
            return $this->paginate($merchants, $total, $page, $limit);
            
        } catch (\Exception $e) {
            return $this->error('获取商户列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取单个商户/代理信息
     */
    public function show(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('商户ID不能为空', 400);
        }
        
        $merchant = $this->userModel->findUserById($id);
        
        if (!$merchant) {
            return $this->error('商户不存在', 404);
        }
        
        // 获取详细信息
        $sql = "SELECT * FROM pay_user WHERE id = ?";
        $merchant = $this->userModel->find($sql, [$id]);
        $merchant['user_type_text'] = $merchant['user_type'] == 0 ? '商户' : '代理';
        
        return $this->success($merchant, '获取商户信息成功');
    }

    /**
     * 创建商户/代理
     */
    public function store(Request $request): Response
    {
        // 验证参数
        $errors = $this->validate($request, [
            'user_name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'user_type' => 'required'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            $data = [
                'user_name' => $request->post('user_name'),
                'email' => $request->post('email'),
                'password' => $request->post('password'),
                'user_type' => $request->post('user_type', 0) // 0: 商户, 1: 代理
            ];
            
            $id = $this->userModel->createUser($data);
            
            return $this->success(['id' => $id], '商户创建成功', 201);
            
        } catch (\Exception $e) {
            return $this->error('创建商户失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新商户/代理信息
     */
    public function update(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('商户ID不能为空', 400);
        }
        
        // 检查商户是否存在
        $merchant = $this->userModel->findUserById($id);
        if (!$merchant) {
            return $this->error('商户不存在', 404);
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
            
            $this->userModel->updateUser($id, $data);
            
            return $this->success(null, '商户信息更新成功');
            
        } catch (\Exception $e) {
            return $this->error('更新商户失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除商户/代理（软删除）
     */
    public function destroy(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('商户ID不能为空', 400);
        }
        
        try {
            // 软删除：将status设置为0
            $sql = "UPDATE pay_user SET status = 0 WHERE id = ?";
            $this->userModel->execute($sql, [$id]);
            
            return $this->success(null, '商户删除成功');
            
        } catch (\Exception $e) {
            return $this->error('删除商户失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取商户/代理统计信息
     */
    public function stats(Request $request): Response
    {
        try {
            $stats = [];
            
            // 总商户数
            $totalMerchants = $this->userModel->find("SELECT COUNT(*) as total FROM pay_user WHERE status = 1 AND user_type = 0")['total'];
            $stats['total_merchants'] = $totalMerchants;
            
            // 总代理数
            $totalAgents = $this->userModel->find("SELECT COUNT(*) as total FROM pay_user WHERE status = 1 AND user_type = 1")['total'];
            $stats['total_agents'] = $totalAgents;
            
            // 今日新增商户
            $todayMerchants = $this->userModel->find("SELECT COUNT(*) as total FROM pay_user WHERE status = 1 AND user_type = 0 AND DATE(reg_time) = CURDATE()")['total'];
            $stats['today_new_merchants'] = $todayMerchants;
            
            // 今日新增代理
            $todayAgents = $this->userModel->find("SELECT COUNT(*) as total FROM pay_user WHERE status = 1 AND user_type = 1 AND DATE(reg_time) = CURDATE()")['total'];
            $stats['today_new_agents'] = $todayAgents;
            
            // 按状态统计
            $statusStats = $this->userModel->findAll("SELECT status, COUNT(*) as count FROM pay_user GROUP BY status");
            $stats['status_stats'] = $statusStats;
            
            return $this->success($stats, '获取统计信息成功');
            
        } catch (\Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 重置商户密码
     */
    public function resetPassword(Request $request): Response
    {
        $id = $request->get('id');
        $newPassword = $request->post('new_password', '123456');
        
        if (empty($id)) {
            return $this->error('商户ID不能为空', 400);
        }
        
        try {
            $this->userModel->updateUser($id, ['password' => $newPassword]);
            
            return $this->success(null, '密码重置成功');
            
        } catch (\Exception $e) {
            return $this->error('密码重置失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 启用/禁用商户
     */
    public function toggleStatus(Request $request): Response
    {
        $id = $request->get('id');
        $status = $request->post('status', 1);
        
        if (empty($id)) {
            return $this->error('商户ID不能为空', 400);
        }
        
        try {
            $sql = "UPDATE pay_user SET status = ? WHERE id = ?";
            $this->userModel->execute($sql, [$status, $id]);
            
            $statusText = $status == 1 ? '启用' : '禁用';
            return $this->success(null, "商户{$statusText}成功");
            
        } catch (\Exception $e) {
            return $this->error('状态更新失败: ' . $e->getMessage(), 500);
        }
    }
}
