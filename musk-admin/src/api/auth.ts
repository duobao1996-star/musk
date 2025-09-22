import request from '@/utils/request'

// 用户信息
export interface UserInfo {
  id: number
  username: string
  email: string
  user_type: string
  role_id: number
}

// 用户登录
export interface LoginData {
  username: string
  password: string
}

export interface LoginResponse {
  token: string
  user: UserInfo
}

// 用户登录
export function login(data: LoginData) {
  return request.post<{ data: LoginResponse }>('/login', data)
}

// 用户登出
export function logout() {
  return request.post('/logout')
}

// 刷新token
export function refreshToken() {
  return request.post('/refresh-token')
}

// 获取用户信息
export function getUserInfo() {
  return request.get<{ data: UserInfo }>('/me')
}
