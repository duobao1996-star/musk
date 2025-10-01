<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\Right;
use app\model\RoleRight;
use app\model\OperationLog;
use think\facade\Db;

class PermissionController extends BaseController
{
    private $rightModel;
    private $roleRightModel;
    private $logModel;

    public function __construct()
    {
        $this->rightModel = new Right();
        $this->roleRightModel = new RoleRight();
        $this->logModel = new OperationLog();
        
    }

    /**
     * 获取权限列表
     */
    public function index(Request $request): Response
    {
        // 检查用户权限
        $user = $request->user ?? null;
        if (!$user) {
            return $this->error('用户未登录', 401);
        }
        
        // 检查是否有权限管理查看权限
        if (!$this->hasPermissionViewPermission($user->user_id)) {
            return $this->error('权限不足', 403);
        }
        
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $search = $request->get('search', '');
            $menu = $request->get('menu', '');

            $query = Db::table('pay_right')->where('is_del', 1);
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->whereLike('right_name', "%{$search}%")
                      ->whereOrLike('description', "%{$search}%");
                });
            }
            if ($menu !== '') {
                $query->where('menu', $menu);
            }

            $total = (clone $query)->count();
            $rights = $query->order('sort','asc')->order('id','asc')
                ->limit(($page-1)*$limit,$limit)->select();
            $rights = $rights->toArray();

            return $this->paginate($rights, $total, $page, $limit, '获取权限列表成功');

        } catch (\Exception $e) {
            return $this->error('获取权限列表失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取权限树
     */
    public function tree(Request $request): Response
    {
        try {
            $tree = $this->rightModel->getRightsTree();
            return $this->success($tree, '获取权限树成功');

        } catch (\Exception $e) {
            return $this->error('获取权限树失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取菜单权限
     */
    public function menu(Request $request): Response
    {
        try {
            // 获取当前用户ID
            $userId = $request->user->user_id ?? null;
            if (!$userId) {
                return $this->error('用户未登录', 401);
            }

            // 获取用户菜单权限
            $rights = $this->rightModel->getUserMenuRights($userId);
            
            // 构建菜单树
            $menuTree = $this->buildMenuTree($rights);
            
            return $this->success($menuTree, '获取菜单权限成功');

        } catch (\Exception $e) {
            return $this->error('获取菜单权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 构建菜单树
     */
    private function buildMenuTree($rights, $pid = 0)
    {
        $tree = [];
        
        foreach ($rights as $right) {
            if ($right['pid'] == $pid) {
                $children = $this->buildMenuTree($rights, $right['id']);
                if (!empty($children)) {
                    $right['children'] = $children;
                }
                
                // 格式化菜单项
                $menuItem = [
                    'id' => $right['id'],
                    'title' => $right['description'],
                    'path' => $right['path'],
                    'icon' => $right['icon'] ?: 'ri:file-list-line',
                    'component' => $right['component'],
                    'redirect' => $right['redirect'],
                    'hidden' => (bool)$right['hidden'],
                    'alwaysShow' => (bool)$right['always_show'],
                    'noCache' => (bool)$right['no_cache'],
                    'affix' => (bool)$right['affix'],
                    'breadcrumb' => (bool)$right['breadcrumb'],
                    'activeMenu' => $right['active_menu'],
                    'children' => $children ?? []
                ];
                
                $tree[] = $menuItem;
            }
        }
        
        return $tree;
    }

    /**
     * 获取权限详情
     */
    public function show(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('权限ID不能为空', 400);
        }

        try {
            $right = $this->rightModel->getRightById($id);
            
            if (!$right) {
                return $this->error('权限不存在', 404);
            }

            return $this->success($right, '获取权限详情成功');

        } catch (\Exception $e) {
            return $this->error('获取权限详情失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 创建权限
     */
    public function store(Request $request): Response
    {
        // 检查用户权限
        $user = $request->user ?? null;
        if (!$user) {
            return $this->error('用户未登录', 401);
        }
        
        // 检查是否有权限创建权限
        if (!$this->hasPermissionCreatePermission($user->user_id)) {
            return $this->error('权限不足', 403);
        }
        
        // 更新允许部分字段，可选校验
        $errors = $this->validate($request, [
            'right_name' => 'min:1',
            'description' => 'min:0'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            $data = [
                'pid' => $request->input('pid'),
                'right_name' => $request->input('right_name'),
                'description' => $request->input('description'),
                'menu' => $request->input('menu', 1),
                'sort' => $request->input('sort', 0),
                'icon' => $request->input('icon')
            ];

            $result = $this->rightModel->createRight($data);
            
            if ($result) {
                // 操作日志
                $adminId = $request->user->user_id ?? null;
                $adminName = $request->user->username ?? '';
                $this->logModel->logOperation(
                    $adminId,
                    $adminName,
                    'create',
                    'permission',
                    '创建权限',
                    [
                        'method' => $request->method(),
                        'url' => $request->path(),
                        'params' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'ip' => $request->getRealIp(),
                        'user_agent' => $request->header('User-Agent')
                    ],
                    ['code' => 200, 'message' => '创建权限成功']
                );
                return $this->success([], '创建权限成功');
            } else {
                return $this->error('创建权限失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('创建权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 更新权限
     */
    public function update(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('权限ID不能为空', 400);
        }

        $errors = $this->validate($request, [
            'right_name' => 'required|min:1',
            'description' => 'required|min:1'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            if (!$this->rightModel->rightExists($id)) {
                return $this->error('权限不存在', 404);
            }

            $data = [
                'pid' => $request->post('pid'),
                'right_name' => $request->post('right_name'),
                'description' => $request->post('description'),
                'menu' => $request->post('menu', 1),
                'sort' => $request->post('sort', 0),
                'icon' => $request->post('icon')
            ];

            $result = $this->rightModel->updateRight($id, $data);
            
            if ($result) {
                // 操作日志
                $adminId = $request->user->user_id ?? null;
                $adminName = $request->user->username ?? '';
                $this->logModel->logOperation(
                    $adminId,
                    $adminName,
                    'update',
                    'permission',
                    '更新权限',
                    [
                        'method' => $request->method(),
                        'url' => $request->path(),
                        'params' => json_encode($data, JSON_UNESCAPED_UNICODE),
                        'ip' => $request->getRealIp(),
                        'user_agent' => $request->header('User-Agent')
                    ],
                    ['code' => 200, 'message' => '更新权限成功']
                );
                return $this->success([], '更新权限成功');
            } else {
                return $this->error('更新权限失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('更新权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 删除权限
     */
    public function destroy(Request $request, $id = null): Response
    {
        
        if (empty($id)) {
            return $this->error('权限ID不能为空', 400);
        }

        try {
            if (!$this->rightModel->rightExists($id)) {
                return $this->error('权限不存在', 404);
            }

            // 检查是否有子权限
            $children = $this->rightModel->getChildRights($id);
            if (!empty($children)) {
                return $this->error('该权限下还有子权限，无法删除', 400);
            }

            // 删除权限的角色关联
            $this->roleRightModel->deleteRightRoles($id);

            $result = $this->rightModel->deleteRight($id);
            
            if ($result) {
                // 操作日志
                $adminId = $request->user->user_id ?? null;
                $adminName = $request->user->username ?? '';
                $this->logModel->logOperation(
                    $adminId,
                    $adminName,
                    'delete',
                    'permission',
                    '删除权限',
                    [
                        'method' => $request->method(),
                        'url' => $request->path(),
                        'params' => json_encode(['id'=>$id], JSON_UNESCAPED_UNICODE),
                        'ip' => $request->getRealIp(),
                        'user_agent' => $request->header('User-Agent')
                    ],
                    ['code' => 200, 'message' => '删除权限成功']
                );
                return $this->success([], '删除权限成功');
            } else {
                return $this->error('删除权限失败', 500);
            }

        } catch (\Exception $e) {
            return $this->error('删除权限失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 获取权限统计
     */
    public function stats(Request $request): Response
    {
        try {
            $sql = "SELECT COUNT(*) as total_rights,
                           SUM(CASE WHEN menu = 1 THEN 1 ELSE 0 END) as menu_rights,
                           SUM(CASE WHEN menu = 0 THEN 1 ELSE 0 END) as action_rights
                    FROM pay_right";
            $res = Db::query($sql);
            $row = $res[0] ?? null;
            $stats = $row ? (array)$row : ['total_rights'=>0,'menu_rights'=>0,'action_rights'=>0];

            return $this->success($stats, '获取权限统计成功');

        } catch (\Exception $e) {
            return $this->error('获取权限统计失败: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 批量删除权限
     */
    public function batchDelete(Request $request): Response
    {
        $ids = $request->post('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的权限', 400);
        }

        try {
            $deleted = 0;
            foreach ($ids as $id) {
                if ($this->rightModel->rightExists($id)) {
                    // 检查是否有子权限
                    $children = $this->rightModel->getChildRights($id);
                    if (empty($children)) {
                        // 删除权限的角色关联
                        $this->roleRightModel->deleteRightRoles($id);
                        $this->rightModel->deleteRight($id);
                        $deleted++;
                    }
                }
            }

            return $this->success(['deleted_count' => $deleted], "成功删除 {$deleted} 个权限");

        } catch (\Exception $e) {
            return $this->error('批量删除权限失败: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * 检查用户是否有权限查看权限
     */
    private function hasPermissionViewPermission($userId): bool
    {
        return $this->checkPermissionPermission($userId, 251); // 权限列表权限
    }
    
    /**
     * 检查用户是否有权限创建权限
     */
    private function hasPermissionCreatePermission($userId): bool
    {
        return $this->checkPermissionPermission($userId, 252); // 权限添加权限
    }
    
    /**
     * 检查用户是否有权限编辑权限
     */
    private function hasPermissionEditPermission($userId): bool
    {
        return $this->checkPermissionPermission($userId, 253); // 权限编辑权限
    }
    
    /**
     * 检查用户是否有权限删除权限
     */
    private function hasPermissionDeletePermission($userId): bool
    {
        return $this->checkPermissionPermission($userId, 253); // 权限编辑权限包含删除
    }
    
    /**
     * 通用权限管理权限检查方法
     */
    private function checkPermissionPermission($userId, $permissionId): bool
    {
        try {
            // 检查是否为超级管理员
            $isSuperAdmin = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role' => 'r'], 'ar.role_id = r.id')
                ->where('ar.admin_id', $userId)
                ->where('r.role_name', '超级管理员')
                ->count() > 0;
                
            if ($isSuperAdmin) {
                return true;
            }
            
            // 检查是否有指定权限
            $hasPermission = Db::table('pay_admin_role')
                ->alias('ar')
                ->join(['pay_role_right' => 'rr'], 'ar.role_id = rr.role_id')
                ->where('ar.admin_id', $userId)
                ->where('rr.right_id', $permissionId)
                ->count() > 0;
                
            return $hasPermission;
        } catch (\Exception $e) {
            error_log("检查权限管理权限失败: " . $e->getMessage());
            return false;
        }
    }
}
