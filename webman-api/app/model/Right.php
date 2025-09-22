<?php

namespace app\model;

use think\facade\Db; // 使用 ThinkPHP 的 Db 作为默认别名

class Right
{
    private static array $rightCache = [];

    public function __construct()
    {
    }

    /**
     * 根据权限名称获取权限信息
     */
    public function getRightByName($rightName)
    {
        $row = Db::table('pay_right')->where('right_name', $rightName)->find();
        return $row ? (array)$row : null;
    }

    /**
     * 根据ID获取权限信息
     */
    public function getRightById($id)
    {
        $row = Db::table('pay_right')->where('id', $id)->find();
        return $row ? (array)$row : null;
    }

    /**
     * 获取所有权限列表
     */
    public function getAllRights()
    {
        // 使用 ThinkPHP 的数据库语法，指定完整表名
        $rows = Db::table('pay_right')
            ->where('is_del', 1)
            ->order('sort', 'asc')
            ->select();
        return $rows->toArray();
    }

    /**
     * 获取菜单权限列表
     */
    public function getMenuRights()
    {
        $rows = Db::table('pay_right')
            ->where('menu', 1)
            ->where('is_del', 1)
            ->order('sort', 'asc')
            ->select();
        return $rows->toArray();
    }

    /**
     * 获取权限树形结构
     */
    public function getRightsTree()
    {
        $rights = $this->getMenuRights();
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
     * 创建权限
     */
    public function createRight($data)
    {
        // 避免重复创建
        $exists = Db::table('pay_right')->where('right_name', $data['right_name'])->where('is_del', 1)->count();
        if ($exists > 0) {
            return false;
        }
        return Db::table('pay_right')->insert([
            'pid' => $data['pid'] ?? null,
            'right_name' => $data['right_name'],
            'description' => $data['description'] ?? '',
            'menu' => $data['menu'] ?? 1,
            'sort' => $data['sort'] ?? 0,
            'icon' => $data['icon'] ?? null,
        ]);
    }

    /**
     * 更新权限
     */
    public function updateRight($id, $data)
    {
        // 名称唯一（排除自身）
        if (!empty($data['right_name'])) {
            $exists = Db::table('pay_right')
                ->where('right_name', $data['right_name'])
                ->where('is_del', 1)
                ->where('id', '<>', $id)
                ->count();
            if ($exists > 0) {
                return false;
            }
        }
        $update = [
            'pid' => $data['pid'] ?? null,
            'right_name' => $data['right_name'],
            'description' => $data['description'] ?? '',
            'menu' => $data['menu'] ?? 1,
            'sort' => $data['sort'] ?? 0,
            'icon' => $data['icon'] ?? null,
        ];
        return Db::table('pay_right')->where('id', $id)->update($update) > 0;
    }

    /**
     * 软删除权限
     */
    public function deleteRight($id)
    {
        return Db::table('pay_right')->where('id', $id)->update([
            'is_del' => 0,
            'delete_time' => new \think\db\Raw('NOW()'),
        ]) > 0;
    }

    /**
     * 恢复软删除的权限
     * 
     * @param int $id 权限ID
     * @return bool 恢复结果
     */
    public function restoreRight(int $id): bool
    {
        return Db::table('pay_right')->where('id', $id)->update([
            'is_del' => 1,
            'delete_time' => null,
        ]) > 0;
    }

    /**
     * 永久删除权限（物理删除）
     * 
     * @param int $id 权限ID
     * @return bool 删除结果
     */
    public function forceDeleteRight(int $id): bool
    {
        return Db::table('pay_right')->where('id', $id)->delete() > 0;
    }

    /**
     * 获取子权限
     */
    public function getChildRights($pid)
    {
        $rows = Db::table('pay_right')
            ->where('pid', $pid)
            ->where('is_del', 1)
            ->order('sort', 'asc')
            ->select();
        return $rows->toArray();
    }

    /**
     * 检查权限是否存在
     */
    public function rightExists($id)
    {
        $count = Db::table('pay_right')->where('id', $id)->where('is_del', 1)->count();
        return $count > 0;
    }

    /**
     * 获取已软删除的权限列表
     * 
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array 权限列表
     */
    public function getDeletedRights(int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;
        $rows = Db::table('pay_right')
            ->field(['id','pid','right_name','description','menu','delete_time'])
            ->where('is_del', 0)
            ->order('delete_time', 'desc')
            ->limit($offset, $limit)
            ->select();
        return $rows->toArray();
    }

    /**
     * 根据URL路径匹配权限
     */
    public function getRightByPath($path, $method)
    {
        $pathKey = $this->normalizePath($path);
        $methodKey = strtoupper($method);

        // 进程内短缓存（key: method|path）
        $cacheKey = $methodKey . '|' . $pathKey;
        if (isset(self::$rightCache[$cacheKey])) {
            return self::$rightCache[$cacheKey];
        }

        // 1) 精确匹配 path + method
        try {
            $row = Db::table('pay_right')
                ->where('path', $pathKey)
                ->where('method', $methodKey)
                ->where('is_del', 1)
                ->find();
            if ($row) {
                return self::$rightCache[$cacheKey] = (array)$row;
            }
        } catch (\Throwable $e) {
            // 忽略，回退到映射逻辑
        }

        // 2) 取消旧映射回退：未匹配则返回 null，由上层生成默认描述
        return self::$rightCache[$cacheKey] = null;
    }

    // 老的映射表已废弃

    /**
     * 标准化路径
     */
    private function normalizePath($path)
    {
        // 处理 //domain.com:port/path 格式的URL
        if (strpos($path, '//') === 0) {
            // 使用正则表达式提取路径部分
            if (preg_match('#//[^/]+(/.*)#', $path, $matches)) {
                $path = $matches[1];
            }
        }
        
        // 移除域名和协议
        if (strpos($path, '://') !== false) {
            $path = parse_url($path, PHP_URL_PATH);
        }
        
        // 移除多余的斜杠
        $path = preg_replace('/\/+/', '/', $path);
        
        // 确保路径以 / 开头
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        
        // 移除查询参数
        $path = strtok($path, '?');
        
        // 移除ID参数，用占位符替换
        $path = preg_replace('/\/\d+$/', '', $path);
        $path = preg_replace('/\/\d+\//', '/', $path);
        
        // 移除结尾的斜杠（除了根路径）
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        
        return $path;
    }

    /**
     * 根据操作类型和模块获取权限描述
     */
    public function getRightDescription($operationType, $module, $path = '')
    {
        // 构建描述映射
        $descriptions = [
            'login' => '用户登录',
            'logout' => '用户登出',
            'view' => '查看',
            'create' => '添加',
            'update' => '编辑',
            'delete' => '删除'
        ];
        
        $moduleNames = [
            'admin' => '管理员',
            'merchant' => '商户',
            'user' => '用户',
            'auth' => '认证'
        ];
        
        $baseDesc = $descriptions[$operationType] ?? $operationType;
        $moduleName = $moduleNames[$module] ?? $module;
        
        // 尝试从权限表获取更精确的描述
        $right = $this->getRightByPath($path, 'GET');
        if ($right && $right['description']) {
            return $right['description'];
        }
        
        return $baseDesc . $moduleName;
    }
}
