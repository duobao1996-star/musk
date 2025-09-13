<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\Right;
use app\model\RoleRight;
use app\support\Database;

class PermissionController extends BaseController
{
    private $rightModel;
    private $roleRightModel;
    private $db;

    public function __construct()
    {
        $this->rightModel = new Right();
        $this->roleRightModel = new RoleRight();
        $this->db = Database::getInstance();
    }

    /**
     * 获取权限列表
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $search = $request->get('search', '');
            $menu = $request->get('menu', '');

            $where = "WHERE 1=1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND (right_name LIKE ? OR description LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            if ($menu !== '') {
                $where .= " AND menu = ?";
                $params[] = $menu;
            }

            $sql = "SELECT * FROM pay_right {$where} ORDER BY sort ASC, id ASC";
            $totalSql = "SELECT COUNT(*) as total FROM pay_right {$where}";
            
            $total = $this->db->find($totalSql, $params)['total'];
            $rights = $this->db->findAll($sql, $params);

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
            $rights = $this->rightModel->getMenuRights();
            return $this->success($rights, '获取菜单权限成功');

        } catch (\Exception $e) {
            return $this->error('获取菜单权限失败: ' . $e->getMessage(), 500);
        }
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
        $errors = $this->validate($request, [
            'right_name' => 'required|min:1',
            'description' => 'required|min:1'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        try {
            $data = [
                'pid' => $request->post('pid'),
                'right_name' => $request->post('right_name'),
                'description' => $request->post('description'),
                'menu' => $request->post('menu', 1),
                'sort' => $request->post('sort', 0),
                'icon' => $request->post('icon')
            ];

            $result = $this->rightModel->createRight($data);
            
            if ($result) {
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
            $sql = "SELECT 
                        COUNT(*) as total_rights,
                        COUNT(CASE WHEN menu = 1 THEN 1 END) as menu_rights,
                        COUNT(CASE WHEN menu = 0 THEN 1 END) as action_rights
                    FROM pay_right";
            $stats = $this->db->find($sql);

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
}
