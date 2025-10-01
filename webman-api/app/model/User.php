<?php

namespace app\model;

use think\facade\Db;
use app\support\SecurityHelper;

/**
 * 用户模型
 * 处理管理员和普通用户的认证、CRUD操作
 */
class User
{
    public function __construct()
    {
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
        $row = Db::table('pay_admin')
            ->where('user_name', $username)
            ->where('status', 1)
            ->find();
        $user = $row ? (array)$row : null;
        
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
        $row = Db::table('pay_admin')
            ->where('email', $email)
            ->where('status', 1)
            ->find();
        $user = $row ? (array)$row : null;
        
        if ($user && password_verify($password, $user['user_password'])) {
            unset($user['user_password']); // 移除密码字段
            return $user;
        }
        return false;
    }

    

    /**
     * 根据ID查找管理员
     */
    public function findAdminById($id)
    {
        $row = Db::table('pay_admin')
            ->where('id', $id)
            ->where('status', 1)
            ->find();
        return $row ? (array)$row : null;
    }

    

    /**
     * 创建管理员
     */
    public function createAdmin($data)
    {
        $insertData = [
            'user_name' => $data['user_name'],
            'user_password' => SecurityHelper::hashPassword($data['password']),
            'email' => $data['email'],
            'role_id' => $data['role_id'] ?? 1,
            'ctime' => Db::raw('NOW()'),
            'status' => 1,
        ];
        return Db::table('pay_admin')->insertGetId($insertData);
    }

    

    /**
     * 更新管理员信息
     */
    public function updateAdmin($id, $data)
    {
        $update = [];
        if (isset($data['user_name'])) {
            $update['user_name'] = $data['user_name'];
        }
        if (isset($data['email'])) {
            $update['email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $update['user_password'] = SecurityHelper::hashPassword($data['password']);
        }
        if (empty($update)) {
            return false;
        }
        $update['etime'] = Db::raw('NOW()');
        return Db::table('pay_admin')->where('id', $id)->update($update) > 0;
    }

    

    /**
     * 执行SQL语句（ThinkORM）
     */
    public function execute($sql, $params = [])
    {
        return Db::execute($sql, $params);
    }

    /**
     * 查询单条记录（ThinkORM）
     */
    public function find($sql, $params = [])
    {
        $rows = Db::query($sql, $params);
        $row = $rows[0] ?? null;
        return $row ? (array)$row : null;
    }

    /**
     * 查询多条记录（ThinkORM）
     */
    public function findAll($sql, $params = [])
    {
        $rows = Db::query($sql, $params);
        return array_map(static fn($r) => (array)$r, $rows);
    }

    /**
     * 软删除管理员
     * 
     * @param int $id 管理员ID
     * @return bool 删除结果
     */
    public function softDeleteAdmin(int $id): bool
    {
        return Db::table('pay_admin')
            ->where('id', $id)
            ->update(['status' => 0, 'etime' => Db::raw('NOW()')]) > 0;
    }

    

    /**
     * 恢复软删除的管理员
     * 
     * @param int $id 管理员ID
     * @return bool 恢复结果
     */
    public function restoreAdmin(int $id): bool
    {
        return Db::table('pay_admin')
            ->where('id', $id)
            ->update(['status' => 1, 'etime' => null]) > 0;
    }

    

    /**
     * 永久删除管理员（物理删除）
     * 
     * @param int $id 管理员ID
     * @return bool 删除结果
     */
    public function forceDeleteAdmin(int $id): bool
    {
        return Db::table('pay_admin')->where('id', $id)->delete() > 0;
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
        $rows = Db::table('pay_admin')
            ->field(['id', 'user_name', 'email', 'role_id', 'ctime', 'etime'])
            ->where('status', 0)
            ->order('etime', 'desc')
            ->limit($offset, $limit)
            ->select();
        return $rows->toArray();
    }

    /**
     * 保存用户令牌
     * 
     * @param int $userId 用户ID
     * @param string $token JWT令牌
     * @param int $expiresIn 过期时间（秒）
     * @return bool 保存结果
     */
    public function saveUserToken(int $userId, string $token, int $expiresIn): bool
    {
        $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
        $createdAt = date('Y-m-d H:i:s');
        
        return Db::table('pay_admin')
            ->where('id', $userId)
            ->update([
                'current_token' => $token,
                'token_expires_at' => $expiresAt,
                'token_created_at' => $createdAt,
                'etime' => Db::raw('NOW()')
            ]) > 0;
    }

    /**
     * 验证令牌是否有效
     * 
     * @param string $token JWT令牌
     * @return array|false 用户信息或false
     */
    public function validateToken(string $token)
    {
        $row = Db::table('pay_admin')
            ->where('current_token', $token)
            ->where('status', 1)
            ->where('token_expires_at', '>', Db::raw('NOW()'))
            ->find();
        
        return $row ? (array)$row : false;
    }

    /**
     * 清除用户令牌（登出）
     * 
     * @param int $userId 用户ID
     * @return bool 清除结果
     */
    public function clearUserToken(int $userId): bool
    {
        return Db::table('pay_admin')
            ->where('id', $userId)
            ->update([
                'current_token' => null,
                'token_expires_at' => null,
                'token_created_at' => null,
                'etime' => Db::raw('NOW()')
            ]) > 0;
    }

    /**
     * 清除指定令牌
     * 
     * @param string $token JWT令牌
     * @return bool 清除结果
     */
    public function clearToken(string $token): bool
    {
        return Db::table('pay_admin')
            ->where('current_token', $token)
            ->update([
                'current_token' => null,
                'token_expires_at' => null,
                'token_created_at' => null,
                'etime' => Db::raw('NOW()')
            ]) > 0;
    }

    /**
     * 清理过期的令牌
     * 
     * @return int 清理的令牌数量
     */
    public function cleanExpiredTokens(): int
    {
        return Db::table('pay_admin')
            ->where('token_expires_at', '<', Db::raw('NOW()'))
            ->whereNotNull('current_token')
            ->update([
                'current_token' => null,
                'token_expires_at' => null,
                'token_created_at' => null,
                'etime' => Db::raw('NOW()')
            ]);
    }
}
