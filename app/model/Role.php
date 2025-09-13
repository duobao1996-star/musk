<?php

namespace app\model;

use app\support\Database;

class Role
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取所有角色
     */
    public function getAllRoles()
    {
        $sql = "SELECT * FROM pay_role WHERE is_del = 1 ORDER BY order_no ASC, id ASC";
        return $this->db->findAll($sql);
    }

    /**
     * 根据ID获取角色
     */
    public function getRoleById($id)
    {
        $sql = "SELECT * FROM pay_role WHERE id = ? AND is_del = 1";
        return $this->db->find($sql, [$id]);
    }

    /**
     * 创建角色
     */
    public function createRole($data)
    {
        $sql = "INSERT INTO pay_role (role_name, order_no, description, create_time, is_del) VALUES (?, ?, ?, NOW(), 1)";
        return $this->db->execute($sql, [
            $data['role_name'],
            $data['order_no'] ?? 0,
            $data['description'] ?? ''
        ]);
    }

    /**
     * 更新角色
     */
    public function updateRole($id, $data)
    {
        $sql = "UPDATE pay_role SET role_name = ?, order_no = ?, description = ?, modify_time = NOW() WHERE id = ? AND is_del = 1";
        return $this->db->execute($sql, [
            $data['role_name'],
            $data['order_no'] ?? 0,
            $data['description'] ?? '',
            $id
        ]);
    }

    /**
     * 删除角色（软删除）
     */
    public function deleteRole($id)
    {
        $sql = "UPDATE pay_role SET is_del = 0 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 获取角色的权限列表
     */
    public function getRoleRights($roleId)
    {
        $sql = "SELECT r.* FROM pay_right r 
                INNER JOIN pay_role_right rr ON r.id = rr.right_id 
                WHERE rr.role_id = ? AND r.menu = 1 
                ORDER BY r.sort ASC";
        return $this->db->findAll($sql, [$roleId]);
    }

    /**
     * 设置角色权限
     */
    public function setRoleRights($roleId, $rightIds)
    {
        // 先删除现有权限
        $deleteSql = "DELETE FROM pay_role_right WHERE role_id = ?";
        $this->db->execute($deleteSql, [$roleId]);

        // 添加新权限
        if (!empty($rightIds)) {
            $insertSql = "INSERT INTO pay_role_right (role_id, right_id) VALUES ";
            $values = [];
            $params = [];
            
            foreach ($rightIds as $rightId) {
                $values[] = "(?, ?)";
                $params[] = $roleId;
                $params[] = $rightId;
            }
            
            $insertSql .= implode(', ', $values);
            $this->db->execute($insertSql, $params);
        }
        
        return true;
    }

    /**
     * 检查角色是否存在
     */
    public function roleExists($id)
    {
        $sql = "SELECT COUNT(*) as count FROM pay_role WHERE id = ? AND is_del = 1";
        $result = $this->db->find($sql, [$id]);
        return $result['count'] > 0;
    }

    /**
     * 获取角色统计信息
     */
    public function getRoleStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_roles,
                    COUNT(CASE WHEN create_time >= CURDATE() THEN 1 END) as today_new
                FROM pay_role WHERE is_del = 1";
        return $this->db->find($sql);
    }
}
