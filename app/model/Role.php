<?php

namespace app\model;

use think\facade\Db;

class Role
{
    public function __construct()
    {
    }

    /**
     * 获取所有角色
     */
    public function getAllRoles()
    {
        $rows = Db::table('pay_role')
            ->where('is_del', 1)
            ->order('order_no', 'asc')
            ->order('id', 'asc')
            ->select();
        return $rows->toArray();
    }

    /**
     * 根据ID获取角色
     */
    public function getRoleById($id)
    {
        $row = Db::table('pay_role')->where('id', $id)->where('is_del', 1)->find();
        return $row ? (array)$row : null;
    }

    /**
     * 检查角色名称是否存在
     */
    public function roleNameExists($roleName, $excludeId = null)
    {
        $query = Db::table('pay_role')
            ->where('role_name', $roleName)
            ->where('is_del', 1);
            
        if ($excludeId) {
            $query->where('id', '<>', $excludeId);
        }
        
        return $query->count() > 0;
    }

    /**
     * 创建角色
     */
    public function createRole($data)
    {
        // 检查角色名称是否已存在
        if ($this->roleNameExists($data['role_name'])) {
            return false; // 返回 false 表示角色名称已存在
        }
        
        return Db::table('pay_role')->insert([
            'role_name' => $data['role_name'],
            'order_no' => $data['order_no'] ?? 0,
            'description' => $data['description'] ?? '',
            'create_time' => Db::raw('NOW()'),
            'is_del' => 1,
        ]);
    }

    /**
     * 更新角色
     */
    public function updateRole($id, $data)
    {
        return Db::table('pay_role')
            ->where('id', $id)
            ->where('is_del', 1)
            ->update([
                'role_name' => $data['role_name'],
                'order_no' => $data['order_no'] ?? 0,
                'description' => $data['description'] ?? '',
                'modify_time' => Db::raw('NOW()'),
            ]) > 0;
    }

    /**
     * 删除角色（软删除）
     */
    public function deleteRole($id)
    {
        return Db::table('pay_role')->where('id', $id)->update(['is_del' => 0]) > 0;
    }

    /**
     * 获取角色的权限列表
     */
    public function getRoleRights($roleId)
    {
        try {
            // 使用原生SQL查询（ThinkORM）
            $sql = "SELECT r.* FROM pay_right r 
                    INNER JOIN pay_role_right rr ON r.id = rr.right_id 
                    WHERE rr.role_id = :role_id AND r.is_del = 1 
                    ORDER BY r.sort ASC";
            $rights = Db::query($sql, ['role_id' => (string)$roleId]);
            return array_map(static fn($r) => (array)$r, $rights);
            
        } catch (\Exception $e) {
            error_log("获取角色权限失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 设置角色权限
     */
    public function setRoleRights($roleId, $rightIds)
    {
        // 先删除现有权限
        Db::table('pay_role_right')->where('role_id', $roleId)->delete();

        // 添加新权限
        if (!empty($rightIds)) {
            $rows = [];
            foreach ($rightIds as $rightId) {
                $rows[] = ['role_id' => (string)$roleId, 'right_id' => $rightId];
            }
            Db::table('pay_role_right')->insertAll($rows);
        }
        
        return true;
    }

    /**
     * 检查角色是否存在
     */
    public function roleExists($id)
    {
        return Db::table('pay_role')->where('id', $id)->where('is_del', 1)->count() > 0;
    }

    /**
     * 获取角色统计信息
     */
    public function getRoleStats()
    {
        $sql = "SELECT COUNT(*) as total_roles,
                       SUM(CASE WHEN create_time >= CURDATE() THEN 1 ELSE 0 END) as today_new
                FROM pay_role WHERE is_del = 1";
        $rows = Db::query($sql);
        $row = $rows[0] ?? null;
        return $row ? (array)$row : ['total_roles' => 0, 'today_new' => 0];
    }
}
