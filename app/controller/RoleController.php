<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\model\Role;
use app\model\RoleRight;
use app\model\Right;
use app\support\Database;
use app\support\Cache;

class RoleController extends BaseController
{
    private $roleModel;
    private $roleRightModel;
    private $rightModel;
    private $db;
    private $cache;

    public function __construct()
    {
        $this->roleModel = new Role();
        $this->roleRightModel = new RoleRight();
        $this->rightModel = new Right();
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
    }

    /**
     * 获取角色列表（优化版本，解决N+1查询问题）
     */
    public function index(Request $request): Response
    {
        try {
            $page = (int)$request->get('page', 1);
            $limit = (int)$request->get('limit', 15);
            $search = $request->get('search', '');

            // 生成缓存键
            $cacheKey = "roles_list_{$page}_{$limit}_" . md5($search);
            
            // 尝试从缓存获取
            $cached = $this->cache->get($cacheKey);
            if ($cached) {
                return $this->success($cached['data'], '获取成功', 200, $cached['pagination']);
            }

            $where = "WHERE r.is_del = 1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND (r.role_name LIKE ? OR r.description LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }

            // 使用JOIN优化查询，一次性获取角色和权限数量
            $sql = "SELECT r.*, COUNT(rr.right_id) as rights_count 
                    FROM pay_role r 
                    LEFT JOIN pay_role_right rr ON r.id = rr.role_id 
                    {$where} 
                    GROUP BY r.id 
                    ORDER BY r.order_no ASC, r.id ASC 
                    LIMIT ? OFFSET ?";
            
            $totalSql = "SELECT COUNT(*) as total FROM pay_role r {$where}";
            
            $params[] = $limit;
            $params[] = ($page - 1) * $limit;
            
            $total = $this->db->find($totalSql, array_slice($params, 0, -2))['total'];
            $roles = $this->db->findAll($sql, $params);

            $result = [
                'data' => $roles,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];

            // 缓存结果（5分钟）
            $this->cache->set($cacheKey, $result, 300);

            return $this->paginate($roles, $total, $page, $limit, '获取角色列表成功');

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

            // 获取角色的权限
            $rights = $this->roleModel->getRoleRights($id);
            $role['rights'] = $rights;

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
                'role_name' => $request->post('role_name'),
                'order_no' => $request->post('order_no', 0),
                'description' => $request->post('description', '')
            ];

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
            $sql = "SELECT COUNT(*) as count FROM pay_admin WHERE role_id = ?";
            $result = $this->db->find($sql, [$id]);
            if ($result['count'] > 0) {
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
        $rightIds = $request->post('right_ids', []);
        
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
     * 获取所有权限树（用于角色权限设置）
     */
    public function allRightsTree(Request $request): Response
    {
        try {
            $tree = $this->rightModel->getRightsTree();
            return $this->success($tree, '获取权限树成功');

        } catch (\Exception $e) {
            return $this->error('获取权限树失败: ' . $e->getMessage(), 500);
        }
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
                    $sql = "SELECT COUNT(*) as count FROM pay_admin WHERE role_id = ?";
                    $result = $this->db->find($sql, [$id]);
                    if ($result['count'] == 0) {
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
