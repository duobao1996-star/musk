<?php

namespace app\model;

use app\support\Database;

class RoleRight
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 获取角色的所有权限
     */
    public function getRoleRights($roleId)
    {
        $sql = "SELECT r.* FROM pay_right r 
                INNER JOIN pay_role_right rr ON r.id = rr.right_id 
                WHERE rr.role_id = ? 
                ORDER BY r.sort ASC";
        return $this->db->findAll($sql, [$roleId]);
    }

    /**
     * 获取权限的所有角色
     */
    public function getRightRoles($rightId)
    {
        $sql = "SELECT ro.* FROM pay_role ro 
                INNER JOIN pay_role_right rr ON ro.id = rr.role_id 
                WHERE rr.right_id = ? AND ro.is_del = 1
                ORDER BY ro.order_no ASC";
        return $this->db->findAll($sql, [$rightId]);
    }

    /**
     * 检查角色是否有某个权限
     */
    public function hasRight($roleId, $rightId)
    {
        $sql = "SELECT COUNT(*) as count FROM pay_role_right WHERE role_id = ? AND right_id = ?";
        $result = $this->db->find($sql, [$roleId, $rightId]);
        return $result['count'] > 0;
    }

    /**
     * 添加角色权限
     */
    public function addRoleRight($roleId, $rightId)
    {
        // 检查是否已存在
        if ($this->hasRight($roleId, $rightId)) {
            return true;
        }

        $sql = "INSERT INTO pay_role_right (role_id, right_id) VALUES (?, ?)";
        return $this->db->execute($sql, [$roleId, $rightId]);
    }

    /**
     * 移除角色权限
     */
    public function removeRoleRight($roleId, $rightId)
    {
        $sql = "DELETE FROM pay_role_right WHERE role_id = ? AND right_id = ?";
        return $this->db->execute($sql, [$roleId, $rightId]);
    }

    /**
     * 批量设置角色权限
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
     * 获取角色的权限树
     */
    public function getRoleRightTree($roleId)
    {
        $sql = "SELECT r.* FROM pay_right r 
                INNER JOIN pay_role_right rr ON r.id = rr.right_id 
                WHERE rr.role_id = ? AND r.menu = 1
                ORDER BY r.sort ASC";
        $rights = $this->db->findAll($sql, [$roleId]);
        
        return $this->buildTree($rights);
    }

    /**
     * 构建权限树
     */
    private function buildTree($rights, $pid = null)
    {
        $tree = [];
        
        foreach ($rights as $right) {
            if ($right['pid'] == $pid) {
                $children = $this->buildTree($rights, $right['id']);
                if (!empty($children)) {
                    $right['children'] = $children;
                }
                $tree[] = $right;
            }
        }
        
        return $tree;
    }

    /**
     * 获取所有权限的树形结构
     */
    public function getAllRightsTree()
    {
        $sql = "SELECT * FROM pay_right WHERE menu = 1 ORDER BY sort ASC";
        $rights = $this->db->findAll($sql);
        
        return $this->buildTree($rights);
    }

    /**
     * 删除角色的所有权限
     */
    public function deleteRoleRights($roleId)
    {
        $sql = "DELETE FROM pay_role_right WHERE role_id = ?";
        return $this->db->execute($sql, [$roleId]);
    }

    /**
     * 删除权限的所有角色关联
     */
    public function deleteRightRoles($rightId)
    {
        $sql = "DELETE FROM pay_role_right WHERE right_id = ?";
        return $this->db->execute($sql, [$rightId]);
    }
}
