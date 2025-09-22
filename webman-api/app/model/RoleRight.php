<?php

namespace app\model;

use think\facade\Db;

class RoleRight
{
    public function __construct()
    {
    }

    /**
     * 获取角色的所有权限
     */
    public function getRoleRights($roleId)
    {
        $rows = Db::table('pay_right')->alias('r')
            ->join(['pay_role_right' => 'rr'], 'r.id = rr.right_id')
            ->where('rr.role_id', $roleId)
            ->order('r.sort', 'asc')
            ->field('r.*')
            ->select()
            ->toArray();
        return $rows;
    }

    /**
     * 获取权限的所有角色
     */
    public function getRightRoles($rightId)
    {
        $rows = Db::table('pay_role')->alias('ro')
            ->join(['pay_role_right' => 'rr'], 'ro.id = rr.role_id')
            ->where('rr.right_id', $rightId)
            ->where('ro.is_del', 1)
            ->order('ro.order_no', 'asc')
            ->field('ro.*')
            ->select()
            ->toArray();
        return $rows;
    }

    /**
     * 检查角色是否有某个权限
     */
    public function hasRight($roleId, $rightId)
    {
        return Db::table('pay_role_right')->where('role_id', $roleId)->where('right_id', $rightId)->count() > 0;
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

        return Db::table('pay_role_right')->insert(['role_id' => $roleId, 'right_id' => $rightId]);
    }

    /**
     * 移除角色权限
     */
    public function removeRoleRight($roleId, $rightId)
    {
        return Db::table('pay_role_right')->where('role_id', $roleId)->where('right_id', $rightId)->delete() > 0;
    }

    /**
     * 批量设置角色权限
     */
    public function setRoleRights($roleId, $rightIds)
    {
        // 先删除现有权限
        Db::table('pay_role_right')->where('role_id', $roleId)->delete();

        // 添加新权限
        if (!empty($rightIds)) {
            $rows = [];
            foreach ($rightIds as $rightId) {
                $rows[] = ['role_id' => $roleId, 'right_id' => $rightId];
            }
            Db::table('pay_role_right')->insertAll($rows);
        }
        
        return true;
    }

    /**
     * 获取角色的权限树
     */
    public function getRoleRightTree($roleId)
    {
        $rows = Db::table('pay_right')->alias('r')
            ->join(['pay_role_right' => 'rr'], 'r.id = rr.right_id')
            ->where('rr.role_id', $roleId)
            ->order('r.sort', 'asc')
            ->field('r.*')
            ->select()
            ->toArray();
        $rights = $rows;
        
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
        $rows = Db::table('pay_right')->order('sort', 'asc')->select()->toArray();
        $rights = $rows;
        
        return $this->buildTree($rights);
    }

    /**
     * 删除角色的所有权限
     */
    public function deleteRoleRights($roleId)
    {
        return Db::table('pay_role_right')->where('role_id', $roleId)->delete() > 0;
    }

    /**
     * 删除权限的所有角色关联
     */
    public function deleteRightRoles($rightId)
    {
        return Db::table('pay_role_right')->where('right_id', $rightId)->delete() > 0;
    }
}
