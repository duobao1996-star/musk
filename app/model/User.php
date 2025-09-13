<?php

namespace app\model;

use app\support\Database;
use app\support\Cache;

/**
 * 用户模型
 * 处理管理员和普通用户的认证、CRUD操作
 */
class User
{
    private Database $db;
    private Cache $cache;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->cache = Cache::getInstance();
    }

    /**
     * 根据用户名和密码验证管理员登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return array|false 用户信息或false
     */
    public function findAdminByCredentials(string $username, string $password)
    {
        $sql = "SELECT id, user_name, email, role_id, status, user_password FROM pay_admin WHERE user_name = ? AND status = 1";
        $user = $this->db->find($sql, [$username]);
        
        if ($user && password_verify($password, $user['user_password'])) {
            unset($user['user_password']); // 移除密码字段
            return $user;
        }
        return false;
    }

    /**
     * 根据邮箱和密码验证管理员登录
     * 
     * @param string $email 邮箱
     * @param string $password 密码
     * @return array|false 用户信息或false
     */
    public function findAdminByEmail(string $email, string $password)
    {
        $sql = "SELECT id, user_name, email, role_id, status, user_password FROM pay_admin WHERE email = ? AND status = 1";
        $user = $this->db->find($sql, [$email]);
        
        if ($user && password_verify($password, $user['user_password'])) {
            unset($user['user_password']); // 移除密码字段
            return $user;
        }
        return false;
    }

    /**
     * 根据用户名和密码查找普通用户
     */
    public function findUserByCredentials($username, $password)
    {
        $sql = "SELECT id, user_name, email, status, login_password FROM pay_user WHERE user_name = ? AND status = 1 AND is_del = 1";
        $user = $this->db->find($sql, [$username]);
        
        if ($user && password_verify($password, $user['login_password'])) {
            unset($user['login_password']); // 移除密码字段
            return $user;
        }
        return false;
    }

    /**
     * 根据邮箱和密码查找普通用户
     */
    public function findUserByEmail($email, $password)
    {
        $sql = "SELECT id, user_name, email, status, login_password FROM pay_user WHERE email = ? AND status = 1 AND is_del = 1";
        $user = $this->db->find($sql, [$email]);
        
        if ($user && password_verify($password, $user['login_password'])) {
            unset($user['login_password']); // 移除密码字段
            return $user;
        }
        return false;
    }

    /**
     * 根据ID查找管理员
     */
    public function findAdminById($id)
    {
        $sql = "SELECT id, user_name, email, role_id, status FROM pay_admin WHERE id = ? AND status = 1";
        return $this->db->find($sql, [$id]);
    }

    /**
     * 根据ID查找普通用户
     */
    public function findUserById($id)
    {
        $sql = "SELECT id, user_name, email, status FROM pay_user WHERE id = ? AND status = 1 AND is_del = 1";
        return $this->db->find($sql, [$id]);
    }

    /**
     * 创建管理员
     */
    public function createAdmin($data)
    {
        $sql = "INSERT INTO pay_admin (user_name, user_password, email, role_id, ctime, status) VALUES (?, ?, ?, ?, NOW(), ?)";
        $this->db->execute($sql, [
            $data['user_name'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['email'],
            $data['role_id'] ?? 1,
            1
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * 创建普通用户
     */
    public function createUser($data)
    {
        $sql = "INSERT INTO pay_user (user_name, login_password, email, reg_time, status) VALUES (?, ?, ?, NOW(), ?)";
        $this->db->execute($sql, [
            $data['user_name'],
            $data['password'],
            $data['email'],
            1
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * 更新管理员信息
     */
    public function updateAdmin($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['user_name'])) {
            $fields[] = "user_name = ?";
            $params[] = $data['user_name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "user_password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "etime = NOW()";
        $params[] = $id;
        
        $sql = "UPDATE pay_admin SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $params);
    }

    /**
     * 更新普通用户信息
     */
    public function updateUser($id, $data)
    {
        $fields = [];
        $params = [];
        
        if (isset($data['user_name'])) {
            $fields[] = "user_name = ?";
            $params[] = $data['user_name'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        
        if (isset($data['password'])) {
            $fields[] = "login_password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $params[] = $id;
        
        $sql = "UPDATE pay_user SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $params);
    }

    /**
     * 执行SQL语句
     */
    public function execute($sql, $params = [])
    {
        return $this->db->execute($sql, $params);
    }

    /**
     * 查询单条记录
     */
    public function find($sql, $params = [])
    {
        return $this->db->find($sql, $params);
    }

    /**
     * 查询多条记录
     */
    public function findAll($sql, $params = [])
    {
        return $this->db->findAll($sql, $params);
    }

    /**
     * 软删除管理员
     * 
     * @param int $id 管理员ID
     * @return bool 删除结果
     */
    public function softDeleteAdmin(int $id): bool
    {
        $sql = "UPDATE pay_admin SET status = 0, etime = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 软删除普通用户
     * 
     * @param int $id 用户ID
     * @return bool 删除结果
     */
    public function softDeleteUser(int $id): bool
    {
        $sql = "UPDATE pay_user SET is_del = 0, delete_time = NOW() WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 恢复软删除的管理员
     * 
     * @param int $id 管理员ID
     * @return bool 恢复结果
     */
    public function restoreAdmin(int $id): bool
    {
        $sql = "UPDATE pay_admin SET status = 1, etime = NULL WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 恢复软删除的普通用户
     * 
     * @param int $id 用户ID
     * @return bool 恢复结果
     */
    public function restoreUser(int $id): bool
    {
        $sql = "UPDATE pay_user SET is_del = 1, delete_time = NULL WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 永久删除管理员（物理删除）
     * 
     * @param int $id 管理员ID
     * @return bool 删除结果
     */
    public function forceDeleteAdmin(int $id): bool
    {
        $sql = "DELETE FROM pay_admin WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 永久删除普通用户（物理删除）
     * 
     * @param int $id 用户ID
     * @return bool 删除结果
     */
    public function forceDeleteUser(int $id): bool
    {
        $sql = "DELETE FROM pay_user WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * 获取已软删除的管理员列表
     * 
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array 管理员列表
     */
    public function getDeletedAdmins(int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, user_name, email, role_id, ctime, etime FROM pay_admin WHERE status = 0 ORDER BY etime DESC LIMIT {$offset}, {$limit}";
        return $this->db->findAll($sql);
    }

    /**
     * 获取已软删除的普通用户列表
     * 
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array 用户列表
     */
    public function getDeletedUsers(int $page = 1, int $limit = 15): array
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT id, user_name, email, user_type, reg_time, delete_time FROM pay_user WHERE is_del = 0 ORDER BY delete_time DESC LIMIT {$offset}, {$limit}";
        return $this->db->findAll($sql);
    }
}
