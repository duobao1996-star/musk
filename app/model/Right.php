<?php

namespace app\model;

use app\support\Database;

class Right
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * 根据权限名称获取权限信息
     */
    public function getRightByName($rightName)
    {
        $sql = "SELECT * FROM pay_right WHERE right_name = ?";
        return $this->db->find($sql, [$rightName]);
    }

    /**
     * 根据ID获取权限信息
     */
    public function getRightById($id)
    {
        $sql = "SELECT * FROM pay_right WHERE id = ?";
        return $this->db->find($sql, [$id]);
    }

    /**
     * 获取所有权限列表
     */
    public function getAllRights()
    {
        $sql = "SELECT * FROM pay_right WHERE is_del = 1 ORDER BY sort ASC";
        return $this->db->findAll($sql);
    }

    /**
     * 获取菜单权限列表
     */
    public function getMenuRights()
    {
        $sql = "SELECT * FROM pay_right WHERE menu = 1 AND is_del = 1 ORDER BY sort ASC";
        return $this->db->findAll($sql);
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
        $sql = "INSERT INTO pay_right (pid, right_name, description, menu, sort, icon) VALUES (?, ?, ?, ?, ?, ?)";
        return $this->db->execute($sql, [
            $data['pid'] ?? null,
            $data['right_name'],
            $data['description'] ?? '',
            $data['menu'] ?? 1,
            $data['sort'] ?? 0,
            $data['icon'] ?? null
        ]);
    }

    /**
     * 更新权限
     */
    public function updateRight($id, $data)
    {
        $sql = "UPDATE pay_right SET pid = ?, right_name = ?, description = ?, menu = ?, sort = ?, icon = ? WHERE id = ?";
        return $this->db->execute($sql, [
            $data['pid'] ?? null,
            $data['right_name'],
            $data['description'] ?? '',
            $data['menu'] ?? 1,
            $data['sort'] ?? 0,
            $data['icon'] ?? null,
            $id
        ]);
    }

    /**
     * 软删除权限
     */
    public function deleteRight($id)
    {
        $sql = "UPDATE pay_right SET is_del = 0, delete_time = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 恢复软删除的权限
     * 
     * @param int $id 权限ID
     * @return bool 恢复结果
     */
    public function restoreRight(int $id): bool
    {
        $sql = "UPDATE pay_right SET is_del = 1, delete_time = NULL WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 永久删除权限（物理删除）
     * 
     * @param int $id 权限ID
     * @return bool 删除结果
     */
    public function forceDeleteRight(int $id): bool
    {
        $sql = "DELETE FROM pay_right WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 获取子权限
     */
    public function getChildRights($pid)
    {
        $sql = "SELECT * FROM pay_right WHERE pid = ? AND is_del = 1 ORDER BY sort ASC";
        return $this->db->findAll($sql, [$pid]);
    }

    /**
     * 检查权限是否存在
     */
    public function rightExists($id)
    {
        $sql = "SELECT COUNT(*) as count FROM pay_right WHERE id = ? AND is_del = 1";
        $result = $this->db->find($sql, [$id]);
        return $result['count'] > 0;
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
        $sql = "SELECT id, pid, right_name, description, menu, delete_time FROM pay_right WHERE is_del = 0 ORDER BY delete_time DESC LIMIT {$offset}, {$limit}";
        return $this->db->findAll($sql);
    }

    /**
     * 根据URL路径匹配权限
     */
    public function getRightByPath($path, $method)
    {
        // 构建权限名称映射
        $rightMapping = $this->getRightMapping();
        
        $pathKey = $this->normalizePath($path);
        $methodKey = strtoupper($method);
        
        
        $rightName = $rightMapping[$pathKey][$methodKey] ?? null;
        
        if ($rightName) {
            return $this->getRightByName($rightName);
        }
        
        return null;
    }

    /**
     * 获取权限映射表
     */
    private function getRightMapping()
    {
        return [
            '/api/admin' => [
                'GET' => 'admin_list',
                'POST' => 'admin_add'
            ],
            '/api/admin/stats' => [
                'GET' => 'admin_stats'
            ],
            '/api/merchant' => [
                'GET' => 'merchant_list',
                'POST' => 'merchant_add'
            ],
            '/api/merchant/stats' => [
                'GET' => 'merchant_stats'
            ],
            '/api/merchant/reset-password' => [
                'POST' => 'merchant_reset_password'
            ],
            '/api/merchant/toggle-status' => [
                'POST' => 'merchant_toggle_status'
            ],
            '/api/logs' => [
                'GET' => 'logs_list'
            ],
            '/api/logs/stats' => [
                'GET' => 'logs_stats'
            ],
            '/api/logs/login' => [
                'GET' => 'logs_login'
            ],
            '/api/logs/clean' => [
                'POST' => 'logs_clean'
            ],
            '/api/logs/sync-descriptions' => [
                'POST' => 'logs_sync'
            ],
            '/api/logs/right-by-url' => [
                'GET' => 'logs_right_by_url'
            ],
            '/api/users' => [
                'GET' => 'user_list'
            ],
            '/api/user' => [
                'POST' => 'user_add'
            ]
        ];
    }

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
