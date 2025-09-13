<?php

namespace app\controller;

use support\Request;
use support\Response;

class ApiController extends BaseController
{
    /**
     * API首页
     */
    public function index(Request $request): Response
    {
        return $this->success([
            'version' => '2.0',
            'server' => 'Webman',
            'php_version' => PHP_VERSION,
            'framework' => 'Webman 2.0'
        ], 'Webman API 2.0 运行正常');
    }

    /**
     * 获取用户列表
     */
    public function users(Request $request): Response
    {
        // 模拟用户数据
        $users = [
            ['id' => 1, 'name' => '张三', 'email' => 'zhangsan@example.com', 'created_at' => '2024-01-01 10:00:00'],
            ['id' => 2, 'name' => '李四', 'email' => 'lisi@example.com', 'created_at' => '2024-01-02 11:00:00'],
            ['id' => 3, 'name' => '王五', 'email' => 'wangwu@example.com', 'created_at' => '2024-01-03 12:00:00'],
        ];

        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 15);
        
        return $this->paginate($users, count($users), $page, $limit);
    }

    /**
     * 获取单个用户
     */
    public function user(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('用户ID不能为空', 400);
        }
        
        // 模拟根据ID获取用户
        $user = [
            'id' => $id,
            'name' => '用户' . $id,
            'email' => 'user' . $id . '@example.com',
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->success($user, '获取用户信息成功');
    }

    /**
     * 创建用户
     */
    public function createUser(Request $request): Response
    {
        // 验证参数
        $errors = $this->validate($request, [
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        $name = $request->post('name');
        $email = $request->post('email');

        // 模拟创建用户
        $user = [
            'id' => rand(1000, 9999),
            'name' => $name,
            'email' => $email,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->success($user, '用户创建成功', 201);
    }

    /**
     * 更新用户
     */
    public function updateUser(Request $request): Response
    {
        $id = $request->get('id');
        
        if (empty($id)) {
            return $this->error('用户ID不能为空', 400);
        }

        // 验证参数
        $errors = $this->validate($request, [
            'name' => 'min:2',
            'email' => 'email'
        ]);

        if (!empty($errors)) {
            return $this->error('参数验证失败', 400, $errors);
        }

        $name = $request->post('name');
        $email = $request->post('email');

        // 模拟更新用户
        $user = [
            'id' => $id,
            'name' => $name ?: '用户' . $id,
            'email' => $email ?: 'user' . $id . '@example.com',
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $this->success($user, '用户更新成功');
    }

    /**
     * 删除用户
     */
    public function deleteUser(Request $request): Response
    {
        $id = $request->get('id');

        if (empty($id)) {
            return $this->error('用户ID不能为空', 400);
        }

        return $this->success(['id' => $id], '用户删除成功');
    }
}
