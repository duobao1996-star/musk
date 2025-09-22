<?php

namespace app\controller;

use support\Request;
use support\Response;
use think\facade\Db;
use app\model\Role;
use app\model\Right;
use app\model\RoleRight;

class RoleController extends BaseController
{
    private $roleModel;
    private $rightModel;
    private $roleRightModel;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->rightModel = new Right();
        $this->roleRightModel = new RoleRight();
    }

    /**
     * 获取角色列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            
            // 查询角色数据
            $roles = Db::table('pay_role')
                ->where('is_del', 1)
                ->order('id', 'asc')
                ->limit(($page - 1) * $limit, $limit)
                ->select();
                
            $total = Db::table('pay_role')->where('is_del', 1)->count();

            return $this->paginate($roles->toArray(), $total, $page, $limit, '获取角色列表成功');

        } catch (\Exception $e) {
            return $this->error('获取角色列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取角色详情
     */
    public function show(Request $request, $id = null): Response
    {
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        try {
            $role = $this->roleModel->getRoleById($id);
            
            if (!$role) {
                return $this->error('角色不存在', 404);
            }

            // 暂时不获取权限，避免错误
            // $rights = $this->roleModel->getRoleRights($id);
            // $role['rights'] = $rights;

            return $this->success($role, '获取角色详情成功');

        } catch (\Exception $e) {
            return $this->error('获取角色详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建角色
     */
    public function store(Request $request): Response
    {
        $errors = $this->validate($request, [
            'role_name' => 'required|min:1'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            $data = [
                'role_name' => $request->post('role_name'),
                'order_no' => $request->post('order_no', 0),
                'description' => $request->post('description', '')
            ];

            // 检查角色名称是否已存在
            if ($this->roleModel->roleNameExists($data['role_name'])) {
                return $this->error('角色名称已存在，请使用其他名称', 400);
            }

            $result = $this->roleModel->createRole($data);
            
            if ($result) {
                return $this->success([], '创建角色成功');
            } else {
                return $this->error('创建角色失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('创建角色失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新角色
     */
    public function update(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        $errors = $this->validate($request, [
            'role_name' => 'required|min:1'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            if (!$this->roleModel->roleExists($id)) {
                return $this->error('角色不存在', 404);
            }

            $data = [
                'role_name' => $request->input('role_name'),
                'order_no' => $request->input('order_no', 0),
                'description' => $request->input('description', '')
            ];

            // 检查角色名称是否已存在（排除当前角色）
            if ($this->roleModel->roleNameExists($data['role_name'], $id)) {
                return $this->error('角色名称已存在，请使用其他名称', 400);
            }

            $result = $this->roleModel->updateRole($id, $data);
            
            if ($result) {
                return $this->success([], '更新角色成功');
            } else {
                return $this->error('更新角色失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('更新角色失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除角色
     */
    public function destroy(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        try {
            if (!$this->roleModel->roleExists($id)) {
                return $this->error('角色不存在', 404);
            }

            // 检查是否有管理员使用该角色
            $count = Db::table('pay_admin')->where('role_id', $id)->count();
            if ($count > 0) {
                return $this->error('该角色下还有管理员，无法删除', 400);
            }

            $result = $this->roleModel->deleteRole($id);
            
            if ($result) {
                return $this->success([], '删除角色成功');
            } else {
                return $this->error('删除角色失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('删除角色失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取角色统计
     */
    public function stats(Request $request): Response
    {
        try {
            $stats = $this->roleModel->getRoleStats();
            return $this->success($stats, '获取角色统计成功');

        } catch (\Exception $e) {
            return $this->error('获取角色统计失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取角色权限
     */
    public function rights(Request $request, $id = null): Response
    {
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        try {
            if (!$this->roleModel->roleExists($id)) {
                return $this->error('角色不存在', 404);
            }

            // 获取角色权限
            $rights = $this->roleModel->getRoleRights($id);
            return $this->success($rights, '获取角色权限成功');

        } catch (\Exception $e) {
            return $this->error('获取角色权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 设置角色权限
     */
    public function setRights(Request $request, $id = null): Response
    {
        // 兼容多种传参：right_ids / rights，支持数组或逗号分隔字符串
        $rightIds = $request->post('right_ids');
        if ($rightIds === null) {
            $rightIds = $request->post('rights', []);
        }
        if (is_string($rightIds)) {
            $rightIds = trim($rightIds);
            $rightIds = $rightIds === '' ? [] : preg_split('/\s*,\s*/', $rightIds);
        }
        if (!is_array($rightIds)) {
            $rightIds = [];
        }
        // 只保留正整数
        $rightIds = array_values(array_filter(array_map(static function ($v) {
            if (is_numeric($v)) {
                $n = (int)$v;
                return $n > 0 ? $n : null;
            }
            return null;
        }, $rightIds)));
        
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        try {
            if (!$this->roleModel->roleExists($id)) {
                return $this->error('角色不存在', 404);
            }

            $result = $this->roleModel->setRoleRights($id, $rightIds);
            
            if ($result) {
                return $this->success([], '设置角色权限成功');
            } else {
                return $this->error('设置角色权限失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('设置角色权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取角色权限树
     */
    public function rightsTree(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('角色ID不能为空', 400);
        }

        try {
            if (!$this->roleModel->roleExists($id)) {
                return $this->error('角色不存在', 404);
            }

            $tree = $this->roleRightModel->getRoleRightTree($id);
            return $this->success($tree, '获取角色权限树成功');

        } catch (\Exception $e) {
            return $this->error('获取角色权限树失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取所有权限树
     */
    public function allRightsTree(Request $request): Response
    {
        try {
            // 使用模型方法，内部已改为原生 SQL
            $rights = $this->rightModel->getAllRights();
            
            return $this->success($rights, '获取权限树成功');
            
        } catch (\Exception $e) {
            return $this->error('获取权限树失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 构建权限树
     */
    private function buildRightsTree($rights, $pid = null)
    {
        $tree = [];
        
        foreach ($rights as $right) {
            // 简化比较逻辑
            if (($right['pid'] == $pid) || ($right['pid'] === null && $pid === null)) {
                $children = $this->buildRightsTree($rights, $right['id']);
                if (!empty($children)) {
                    $right['children'] = $children;
                }
                $tree[] = $right;
            }
        }
        
        return $tree;
    }

    /**
     * 批量删除角色
     */
    public function batchDelete(Request $request): Response
    {
        $ids = $request->post('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的角色', 400);
        }

        try {
            $deleted = 0;
            foreach ($ids as $id) {
                if ($this->roleModel->roleExists($id)) {
                    // 检查是否有管理员使用该角色
                    $count = Db::table('pay_admin')->where('role_id', $id)->count();
                    if ($count == 0) {
                        $this->roleModel->deleteRole($id);
                        $deleted++;
                    }
                }
            }

            return $this->success(['deleted_count' => $deleted], "成功删除 {$deleted} 个角色");

        } catch (\Exception $e) {
            return $this->error('批量删除角色失败: ' . $e->getMessage(), 500);
        }
    }
}
