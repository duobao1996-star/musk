<?php

namespace app\controller;

use support\Request;
use support\Response;
use think\facade\Db;
use app\model\Role;
use app\model\Right;
use app\model\RoleRight;
use app\model\OperationLog;

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
                // 日志
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'create', 'role', '创建角色', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['role_name' => $data['role_name']], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '创建角色成功']);
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
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'update', 'role', '更新角色', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '更新角色成功']);
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
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'delete', 'role', '删除角色', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '删除角色成功']);
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
     * 获取角色权限（仅菜单权限）
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

            // 获取角色权限（仅菜单权限）
            $rights = $this->roleModel->getRoleRights($id);
            
            // 过滤出菜单权限
            $menuRights = array_filter($rights, function($right) {
                return isset($right['is_menu']) && $right['is_menu'] == 1;
            });
            
            return $this->success(array_values($menuRights), '获取角色权限成功');

        } catch (\Exception $e) {
            return $this->error('获取角色权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 设置角色权限（仅菜单权限）
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

            // 验证权限ID是否都是菜单权限
            $menuRights = $this->rightModel->getMenuRights();
            $menuRightIds = array_column($menuRights, 'id');
            $invalidIds = array_diff($rightIds, $menuRightIds);
            
            if (!empty($invalidIds)) {
                return $this->error('只能设置菜单权限，API权限由系统自动管理', 400);
            }

            // 自动添加父级权限，确保菜单能正常显示
            $rightIds = $this->addParentPermissions($rightIds, $menuRights);

            $result = $this->roleModel->setRoleRights($id, $rightIds);
            
            if ($result) {
                $op = new OperationLog();
                $user = $request->user ?? null;
                $op->logOperation($user->user_id ?? null, $user->username ?? '', 'update', 'role', '设置角色权限', [
                    'method' => $request->method(),
                    'url' => $request->path(),
                    'params' => json_encode(['id' => $id, 'rights' => $rightIds], JSON_UNESCAPED_UNICODE),
                    'ip' => $request->getRealIp(),
                    'user_agent' => $request->header('User-Agent')
                ], ['code' => 200, 'message' => '设置角色权限成功']);
                return $this->success([], '设置角色权限成功');
            } else {
                return $this->error('设置角色权限失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('设置角色权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 自动添加父级权限
     */
    private function addParentPermissions($rightIds, $menuRights)
    {
        // 创建权限ID到权限信息的映射
        $rightMap = [];
        foreach ($menuRights as $right) {
            $rightMap[$right['id']] = $right;
        }
        
        $finalRightIds = $rightIds;
        
        // 为每个权限添加其所有父级权限
        foreach ($rightIds as $rightId) {
            $parentIds = $this->getParentPermissionIds($rightId, $rightMap);
            $finalRightIds = array_merge($finalRightIds, $parentIds);
        }
        
        // 去重并返回
        return array_unique($finalRightIds);
    }
    
    
    /**
     * 递归获取权限的所有父级权限ID
     */
    private function getParentPermissionIds($rightId, $rightMap)
    {
        $parentIds = [];
        
        if (!isset($rightMap[$rightId])) {
            return $parentIds;
        }
        
        $right = $rightMap[$rightId];
        
        // 如果有父级权限（pid > 0），递归获取
        if ($right['pid'] > 0) {
            $parentIds[] = $right['pid'];
            $grandParentIds = $this->getParentPermissionIds($right['pid'], $rightMap);
            $parentIds = array_merge($parentIds, $grandParentIds);
        }
        
        return $parentIds;
    }
    

    /**
     * 获取角色权限树（仅菜单权限）
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

            // 获取角色权限（仅菜单权限）
            $rights = $this->roleModel->getRoleRights($id);
            
            // 过滤出菜单权限
            $menuRights = array_filter($rights, function($right) {
                return isset($right['is_menu']) && $right['is_menu'] == 1;
            });
            
            // 构建权限树
            $tree = $this->buildRightsTree(array_values($menuRights));
            
            return $this->success($tree, '获取角色权限树成功');

        } catch (\Exception $e) {
            return $this->error('获取角色权限树失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取所有权限树（仅菜单权限）
     */
    public function allRightsTree(Request $request): Response
    {
        try {
            // 只获取菜单权限，不包含API权限
            $rights = $this->rightModel->getMenuRights();
            
            // 构建权限树
            $tree = $this->buildRightsTree($rights);
            
            return $this->success($tree, '获取权限树成功');
            
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
